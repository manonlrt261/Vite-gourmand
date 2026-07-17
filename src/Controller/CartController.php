<?php

namespace App\Controller;

use App\Service\MongoStatsService;
use App\Validator\InputValidator;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

// Controleur du panier, de la validation de commande et de la confirmation.
class CartController extends AbstractController
{
    private const BORDEAUX_LATITUDE = 44.837789;
    private const BORDEAUX_LONGITUDE = -0.57918;
    private const DELIVERY_BASE_PRICE = 5.00;
    private const DELIVERY_PRICE_PER_KM = 0.59;

    private const REQUIRED_CHECKOUT_FIELDS = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse_livraison',
        'code_postal_livraison',
        'ville_livraison',
        'date_prestation',
        'heure_livraison',
    ];

    // Affiche le panier sans ajouter de nouveau menu.
    public function index(Request $request, Connection $connection): Response
    {
        return $this->renderCart($request, $connection);
    }

    // Ancienne route conservee pour compatibilite : elle ne modifie plus le panier en GET.
    public function show(int $id): Response
    {
        return $this->redirectToRoute('cart_index');
    }

    // Ajoute un menu au panier depuis une action rapide.
    public function add(int $id, Request $request, Connection $connection): Response
    {
        // Protection CSRF de l ajout rapide au panier depuis les cartes menus.
        if (!$this->isValidCartCsrf($request)) {
            if (!$request->isXmlHttpRequest()) {
                $this->addFlash('cart_error', 'Le formulaire d ajout au panier a expire, veuillez reessayer.');

                return $this->redirectToRoute('menu_show', ['id' => $id]);
            }

            return $this->json([
                'success' => false,
                'message' => 'Formulaire d ajout au panier invalide.',
            ], 403);
        }

        if (!$this->addMenuToCart($id, $request, $connection)) {
            if (!$request->isXmlHttpRequest()) {
                throw $this->createNotFoundException('Menu introuvable.');
            }

            return $this->json([
                'success' => false,
                'message' => 'Ce menu est introuvable ou indisponible.',
            ], 404);
        }

        if (!$request->isXmlHttpRequest()) {
            return $this->redirectToRoute('cart_index');
        }

        return $this->json([
            'success' => true,
            'message' => 'Menu ajoute au panier.',
            'cartCount' => count($this->getCartItems($request)),
        ]);
    }

    // Met a jour le panier et renvoie le nouveau recapitulatif.
    public function update(Request $request, Connection $connection): Response
    {
        // Le token CSRF confirme que la demande vient bien du formulaire du site.
        if (!$this->isValidCartCsrf($request)) {
            return $request->isXmlHttpRequest()
                ? $this->json(['success' => false, 'message' => 'Formulaire de panier invalide.'], 403)
                : $this->redirectToRoute('cart_index');
        }

        $cartItems = $this->getCartItems($request);
        $quantities = $request->request->all('quantities');

        if ($cartItems === [] || $quantities === []) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false], 400);
            }

            return $this->redirectToRoute('cart_index');
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, personnes_minimum
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $minimumsById = [];
        foreach ($menus as $menu) {
            $minimumsById[(int) $menu['menu_id']] = (int) $menu['personnes_minimum'];
        }

        foreach ($cartItems as $menuId => $item) {
            $minimum = $minimumsById[$menuId] ?? 1;
            $requestedQuantity = (int) ($quantities[$menuId] ?? $minimum);
            $cartItems[$menuId]['nombre_personnes'] = max($minimum, $requestedQuantity);
        }

        $this->saveCartItems($request, $cartItems);

        if ($request->isXmlHttpRequest()) {
            return $this->jsonCartSummary($request, $connection);
        }

        return $this->redirectToRoute('cart_index');
    }

    // Supprime un menu du panier.
    public function remove(int $id, Request $request, Connection $connection): Response
    {
        // Protection CSRF : evite qu un autre site supprime un menu du panier a la place du client.
        if (!$this->isValidCartCsrf($request)) {
            return $request->isXmlHttpRequest()
                ? $this->json(['success' => false, 'message' => 'Formulaire de suppression invalide.'], 403)
                : $this->redirectToRoute('cart_index');
        }

        $cartItems = $this->getCartItems($request);
        unset($cartItems[$id]);
        $this->saveCartItems($request, $cartItems);

        if ($request->isXmlHttpRequest()) {
            if ($cartItems === []) {
                return $this->json([
                    'success' => true,
                    'isEmpty' => true,
                ]);
            }

            return $this->jsonCartSummary($request, $connection);
        }

        return $this->redirectToRoute('cart_index');
    }

    // Verifie le panier, cree les commandes en base et synchronise les statistiques.
    public function checkout(Request $request, Connection $connection, MongoStatsService $mongoStatsService, MailerInterface $mailer): Response
    {
        $session = $request->getSession();
        $isConnected = $this->getUser() !== null || $session->has('utilisateur_id');

        if (!$isConnected) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('cart_index')]);
        }

        // La validation finale de commande est sensible : elle doit venir du vrai formulaire panier.
        if (!$this->isValidCartCsrf($request)) {
            $this->addFlash('cart_error', 'Le formulaire a expire, veuillez reessayer.');

            return $this->redirectToRoute('cart_index');
        }

        $checkoutData = [];
        foreach (self::REQUIRED_CHECKOUT_FIELDS as $field) {
            $checkoutData[$field] = trim((string) $request->request->get($field));
        }

        foreach ($checkoutData as $value) {
            if ($value === '') {
                $this->addFlash('cart_error', 'Tous les champs obligatoires doivent être renseignés.');

                return $this->redirectToRoute('cart_index');
            }
        }

        if (!filter_var($checkoutData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('cart_error', 'Veuillez renseigner une adresse email valide.');

            return $this->redirectToRoute('cart_index');
        }

        // Controle les informations de commande cote serveur avant creation en base.
        $checkoutError = $this->validateCheckoutData($checkoutData);
        if ($checkoutError !== null) {
            $this->addFlash('cart_error', $checkoutError);

            return $this->redirectToRoute('cart_index');
        }

        $this->updateCartQuantitiesFromRequest($request, $connection);
        $summary = $this->getCartSummary($request, $connection, $checkoutData);

        if (!$summary || $summary['items'] === []) {
            $this->addFlash('cart_error', 'Votre panier est vide.');

            return $this->redirectToRoute('cart_index');
        }

        $leadTimeError = $this->validateMenuLeadTimes($summary['items'], $checkoutData['date_prestation']);
        if ($leadTimeError !== null) {
            $this->addFlash('cart_error', $leadTimeError);

            return $this->redirectToRoute('cart_index');
        }

        $userId = (int) $session->get('utilisateur_id');
        $statusId = (int) ($connection->fetchOne(
            'SELECT statut_id FROM statuts_commande WHERE code = ? LIMIT 1',
            ['en_attente']
        ) ?: 1);
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $orderIds = [];
        // Le client ne peut demander du materiel que si au moins un menu du panier le propose en base.
        $materialRequested = $request->request->getBoolean('pret_materiel');
        $hasMaterialMenu = false;
        foreach ($summary['items'] as $item) {
            if ($this->menuProvidesMaterial($item['menu'] ?? [])) {
                $hasMaterialMenu = true;
                break;
            }
        }
        $pretMateriel = $materialRequested && $hasMaterialMenu ? 1 : 0;

        // La table des menus commandes est verifiee avant la transaction :
        // une requete CREATE TABLE peut fermer automatiquement une transaction MySQL.
        $this->ensureOrderMenuTable($connection);

        try {
            // La transaction regroupe la commande, ses menus et son historique.
            $connection->beginTransaction();

            $commandeColumns = $this->getTableColumns($connection, 'commandes');
            $historiqueColumns = $this->getTableColumns($connection, 'historique_statuts_commande');

            $firstItem = $summary['items'][0];
            $totalPeople = array_sum(array_map(static fn (array $item): int => (int) $item['nombrePersonnes'], $summary['items']));
            $commandeData = [
                'utilisateur_id' => $userId,
                // Cette colonne reste remplie pour garder la compatibilite avec les anciennes pages.
                'menu_id' => (int) $firstItem['menu']['menu_id'],
                'date_commande' => $today,
                'date_prestation' => $checkoutData['date_prestation'],
                'heure_livraison' => $checkoutData['heure_livraison'],
                'heure_de_livraison' => $checkoutData['heure_livraison'],
                'adresse_livraison' => $checkoutData['adresse_livraison'],
                'ville_livraison' => $checkoutData['ville_livraison'],
                'code_postal_livraison' => $checkoutData['code_postal_livraison'],
                'nombre_personnes' => $totalPeople,
                'prix_menu' => $summary['prixMenu'] - $summary['reduction'],
                'prix_livraison' => $summary['prixLivraison'],
                'prix_total' => $summary['prixTotal'],
                'pret_materiel' => $pretMateriel,
                'motif_annulation' => '',
                'created_at' => $now,
                'updated_at' => $now,
                'statut_id' => $statusId,
            ];

            $connection->insert('commandes', array_intersect_key($commandeData, array_flip($commandeColumns)));
            $orderId = (int) $connection->lastInsertId();
            $orderIds[] = $orderId;

            foreach ($summary['items'] as $item) {
                $connection->insert('commande_menus', [
                    'commande_id' => $orderId,
                    'menu_id' => (int) $item['menu']['menu_id'],
                    'nombre_personnes' => (int) $item['nombrePersonnes'],
                    'prix_par_personne' => (float) $item['menu']['prix_par_personne'],
                    'prix_menu' => (float) ($item['prixLigne'] - $item['reductionLigne']),
                    'reduction' => (float) $item['reductionLigne'],
                    'created_at' => $now,
                ]);
            }

            if ($historiqueColumns !== []) {
                $historiqueData = [
                    'commande_id' => $orderId,
                    'statut_id' => $statusId,
                    'date_changement' => $now,
                    'commentaire' => 'Commande créée depuis le panier.',
                ];

                $connection->insert(
                    'historique_statuts_commande',
                    array_intersect_key($historiqueData, array_flip($historiqueColumns))
                );
            }

            // Les menus ont deja ete enregistres dans commande_menus : on evite l'ancien comportement qui creait une commande par menu.
            $summary['items'] = [];

            foreach ($summary['items'] as $index => $item) {
                $deliveryPrice = $index === 0 ? $summary['prixLivraison'] : 0;
                $lineTotalAfterDiscount = $item['prixLigne'] - $item['reductionLigne'];
                $commandeData = [
                    'utilisateur_id' => $userId,
                    'menu_id' => (int) $item['menu']['menu_id'],
                    'date_commande' => $today,
                    'date_prestation' => $checkoutData['date_prestation'],
                    'heure_livraison' => $checkoutData['heure_livraison'],
                    'heure_de_livraison' => $checkoutData['heure_livraison'],
                    'adresse_livraison' => $checkoutData['adresse_livraison'],
                    'ville_livraison' => $checkoutData['ville_livraison'],
                    'code_postal_livraison' => $checkoutData['code_postal_livraison'],
                    'nombre_personnes' => $item['nombrePersonnes'],
                    'prix_menu' => $lineTotalAfterDiscount,
                    'prix_livraison' => $deliveryPrice,
                    'prix_total' => $lineTotalAfterDiscount + $deliveryPrice,
                    'pret_materiel' => $pretMateriel,
                    'motif_annulation' => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                    'statut_id' => $statusId,
                ];

                $connection->insert('commandes', array_intersect_key($commandeData, array_flip($commandeColumns)));
                $orderId = (int) $connection->lastInsertId();
                $orderIds[] = $orderId;

                if ($historiqueColumns !== []) {
                    $historiqueData = [
                        'commande_id' => $orderId,
                        'statut_id' => $statusId,
                        'date_changement' => $now,
                        'commentaire' => 'Commande créée depuis le panier.',
                    ];

                    $connection->insert(
                        'historique_statuts_commande',
                        array_intersect_key($historiqueData, array_flip($historiqueColumns))
                    );
                }
            }

            // On valide uniquement si MySQL garde encore une transaction active.
            try {
                if ($connection->isTransactionActive()) {
                    $connection->commit();
                }
            } catch (\Doctrine\DBAL\Exception\NoActiveTransaction) {
            }
        } catch (\Throwable $exception) {
            try {
                if ($connection->isTransactionActive()) {
                    $connection->rollBack();
                }
            } catch (\Doctrine\DBAL\Exception\NoActiveTransaction) {
            }
            $this->addFlash('cart_error', 'Impossible d’enregistrer la commande pour le moment.');

            return $this->redirectToRoute('cart_index');
        }

        $this->saveCartItems($request, []);
        $session->set('last_order_ids', $orderIds);
        $orders = $this->getOrdersForConfirmation($connection, $orderIds, $userId);
        $this->sendOrderConfirmationEmail($mailer, $orders, $checkoutData);
        $mongoStatsService->getOrdersByMenuDocuments($connection);

        return $this->redirectToRoute('order_confirmation');
    }

    // Affiche la page de confirmation apres validation de commande.
    public function confirmation(Request $request, Connection $connection): Response
    {
        $session = $request->getSession();
        $orderIds = $session->get('last_order_ids', []);
        $userId = (int) $session->get('utilisateur_id');
        $orders = $userId > 0 ? $this->getOrdersForConfirmation($connection, $orderIds, $userId) : [];

        return $this->render('cart/confirmation.html.twig', [
            'orderIds' => $orderIds,
            'orders' => $orders,
        ]);
    }

    /**
     * @param list<int> $orderIds
     * @return list<array<string, mixed>>
     */
    // Recupere les commandes a afficher sur la page de confirmation.
    private function getOrdersForConfirmation(Connection $connection, array $orderIds, int $userId): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds)));

        if ($orderIds === []) {
            return [];
        }

        $orders = $connection->fetchAllAssociative(
            'SELECT c.commande_id, c.date_commande, c.date_prestation, c.heure_de_livraison,
                    c.adresse_livraison, c.ville_livraison, c.code_postal_livraison,
                    c.nombre_personnes, c.prix_menu, c.prix_livraison, c.prix_total, c.statut_id,
                    m.menu_id, m.nom_menu, m.description AS menu_description, m.prix_par_personne,
                    m.image_url AS menu_image_url, m.image_alt AS menu_image_alt,
                    COALESCE(sc.libelle, "En attente") AS statut_libelle,
                    COALESCE(sc.code, "en_attente") AS statut_code
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
            WHERE c.commande_id IN (?) AND c.utilisateur_id = ?
             ORDER BY c.commande_id ASC',
            [$orderIds, $userId],
            [ArrayParameterType::INTEGER, ParameterType::INTEGER]
        );

        foreach ($orders as $index => $order) {
            $orders[$index]['orderMenus'] = $this->getOrderMenuLines($connection, $order);
            foreach ($orders[$index]['orderMenus'] as $menuIndex => $orderMenu) {
                $orders[$index]['orderMenus'][$menuIndex]['mealItems'] = $this->getOrderMealItems($connection, (int) $orderMenu['menu_id']);
            }
            $orders[$index]['mealItems'] = $this->getOrderMealItems($connection, (int) $order['menu_id']);
            $orders[$index]['statusHistory'] = $this->getOrderStatusHistory($connection, (int) $order['commande_id'], $order);
        }

        return $orders;
    }

    // Email 3 : prepare et envoie au client le recapitulatif complet de sa commande.
    private function sendOrderConfirmationEmail(MailerInterface $mailer, array $orders, array $checkoutData): void
    {
        if ($orders === []) {
            return;
        }

        $to = (string) ($checkoutData['email'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';
        $firstName = htmlspecialchars((string) ($checkoutData['prenom'] ?? ''), ENT_QUOTES, 'UTF-8');
        $totalOrder = array_sum(array_map(static fn (array $order): float => (float) $order['prix_total'], $orders));

        $lines = [
            '<h1>Confirmation de votre commande</h1>',
            '<p>Bonjour ' . $firstName . ',</p>',
            '<p>Nous avons bien reçu votre commande Vite & Gourmand.</p>',
            '<p>Elle est actuellement en attente de validation par notre équipe. Vous trouverez ci-dessous le récapitulatif de votre demande.</p>',
        ];

        foreach ($orders as $order) {
            $subtotal = 0;
            $discount = 0;
            foreach (($order['orderMenus'] ?? []) as $orderMenu) {
                $subtotal += (float) $orderMenu['prix_par_personne'] * (int) $orderMenu['nombre_personnes'];
                $discount += (float) $orderMenu['reduction'];
            }

            $lines[] = '<hr>';
            $lines[] = '<h2>Commande n&deg;' . (int) $order['commande_id'] . '</h2>';
            $lines[] = '<h3>Prestation</h3>';
            $lines[] = '<p><strong>Date de la prestation :</strong> ' . htmlspecialchars((string) $order['date_prestation'], ENT_QUOTES, 'UTF-8') . '</p>';
            $lines[] = '<p><strong>Heure de livraison :</strong> ' . htmlspecialchars((string) $order['heure_de_livraison'], ENT_QUOTES, 'UTF-8') . '</p>';
            $lines[] = '<p><strong>Adresse :</strong> ' . htmlspecialchars((string) $order['adresse_livraison'], ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars((string) $order['code_postal_livraison'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) $order['ville_livraison'], ENT_QUOTES, 'UTF-8') . '</p>';
            $lines[] = '<h3>Détail des menus</h3>';
            foreach (($order['orderMenus'] ?? []) as $orderMenu) {
                $lines[] = '<p><strong>Menu :</strong> ' . htmlspecialchars((string) $orderMenu['nom_menu'], ENT_QUOTES, 'UTF-8') . '</p>';
                $lines[] = '<p><strong>Nombre de personnes :</strong> ' . (int) $orderMenu['nombre_personnes'] . '</p>';
                foreach (($orderMenu['mealItems'] ?? []) as $category => $item) {
                    if (!$item) {
                        continue;
                    }

                    $categoryLabel = htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8');
                    $mealName = htmlspecialchars((string) ($item['nom'] ?? ''), ENT_QUOTES, 'UTF-8');
                    $lines[] = '<p><strong>' . $categoryLabel . ' :</strong> ' . $mealName . '</p>';
                }
            }
            $lines[] = '<h3>Tarifs</h3>';
            $lines[] = '<p><strong>Sous-total :</strong> ' . number_format($subtotal, 2, ',', ' ') . ' &euro;</p>';
            if ($discount > 0) {
                $lines[] = '<p><strong>Réduction :</strong> - ' . number_format($discount, 2, ',', ' ') . ' &euro;</p>';
            }
            $lines[] = '<p><strong>Livraison :</strong> ' . number_format((float) $order['prix_livraison'], 2, ',', ' ') . ' &euro;</p>';
            $lines[] = '<p><strong>Total TTC :</strong> ' . number_format((float) $order['prix_total'], 2, ',', ' ') . ' &euro;</p>';
            $lines[] = '<p><strong>Statut :</strong> ' . htmlspecialchars((string) $order['statut_libelle'], ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $lines[] = '<hr>';
        $lines[] = '<p><strong>Total de votre commande :</strong> ' . number_format($totalOrder, 2, ',', ' ') . ' &euro;</p>';
        $lines[] = '<p>Vous pouvez suivre votre commande depuis votre espace client.</p>';
        $lines[] = '<p>À très bientôt,<br>L’équipe Vite & Gourmand</p>';

        try {
            // L'email reprend les informations visibles sur la page de confirmation de commande.
            $mailer->send((new Email())
                ->from($from)
                ->to($to)
                ->subject('Confirmation de votre commande - Vite & Gourmand')
                ->html(implode("\n", $lines)));
        } catch (\Throwable) {
            // La commande doit rester valide meme si le SMTP local n'est pas encore configure.
        }
    }

    /**
     * @param array<string, string> $checkoutData
     */
    // Verifie les formats, les dates et les longueurs du formulaire panier.
    private function validateCheckoutData(array $checkoutData): ?string
    {
        if (!InputValidator::isValidPhone($checkoutData['telephone'] ?? '')) {
            return 'Veuillez renseigner un numero de telephone valide.';
        }

        if (!InputValidator::isValidPostalCode($checkoutData['code_postal_livraison'] ?? '')) {
            return 'Veuillez renseigner un code postal de livraison valide a 5 chiffres.';
        }

        if (!InputValidator::isFutureOrTodayDate($checkoutData['date_prestation'] ?? '')) {
            return 'La date de prestation doit etre valide et ne peut pas etre dans le passe.';
        }

        if (!InputValidator::isValidTime($checkoutData['heure_livraison'] ?? '')) {
            return 'Veuillez renseigner une heure de livraison valide.';
        }

        if (!InputValidator::isTimeBetween($checkoutData['heure_livraison'] ?? '', '08:00', '22:00')) {
            return 'Les livraisons sont possibles entre 8h00 et 22h00';
        }

        $lengths = [
            'nom' => 100,
            'prenom' => 100,
            'email' => 255,
            'telephone' => 20,
            'adresse_livraison' => 255,
            'code_postal_livraison' => 10,
            'ville_livraison' => 100,
        ];

        foreach ($lengths as $field => $maxLength) {
            if (!InputValidator::hasMaxLength($checkoutData[$field] ?? '', $maxLength)) {
                return 'Certaines informations de commande sont trop longues.';
            }
        }

        return null;
    }

    /**
     * @return array<string, array<string, mixed>|false>
     */
    // Recupere l entree, le plat et le dessert associes a un menu commande.
    private function getOrderMealItems(Connection $connection, int $menuId): array
    {
        return [
            'Entree' => $connection->fetchAssociative(
                'SELECT nom_entree AS nom, description, image_url, image_alt
                 FROM entree
                 WHERE menu_id = ?
                 LIMIT 1',
                [$menuId]
            ),
            'Plat' => $connection->fetchAssociative(
                'SELECT nom_plat AS nom, description, image_url, image_alt
                 FROM plat
                 WHERE menu_id = ?
                 LIMIT 1',
                [$menuId]
            ),
            'Dessert' => $connection->fetchAssociative(
                'SELECT nom_dessert AS nom, description, image_url, image_alt
                 FROM dessert
                 WHERE menu_id = ?
                 LIMIT 1',
                [$menuId]
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Recupere les menus contenus dans une commande, avec une compatibilite pour les anciennes commandes a menu unique.
    private function getOrderMenuLines(Connection $connection, array $order): array
    {
        try {
            $lines = $connection->fetchAllAssociative(
                'SELECT cm.commande_menu_id, cm.menu_id, cm.nombre_personnes,
                        cm.prix_par_personne, cm.prix_menu, cm.reduction,
                        m.nom_menu, m.description AS menu_description,
                        m.image_url AS menu_image_url, m.image_alt AS menu_image_alt
                 FROM commande_menus cm
                 LEFT JOIN menus m ON m.menu_id = cm.menu_id
                 WHERE cm.commande_id = ?
                 ORDER BY cm.commande_menu_id ASC',
                [(int) $order['commande_id']]
            );

            if ($lines !== []) {
                return $lines;
            }
        } catch (\Throwable) {
            // Si la table commande_menus n existe pas encore, on garde l ancien affichage.
        }

        return [[
            'commande_menu_id' => null,
            'menu_id' => (int) $order['menu_id'],
            'nombre_personnes' => (int) $order['nombre_personnes'],
            'prix_par_personne' => (float) $order['prix_par_personne'],
            'prix_menu' => (float) $order['prix_menu'],
            'reduction' => max(0, ((float) $order['prix_par_personne'] * (int) $order['nombre_personnes']) - (float) $order['prix_menu']),
            'nom_menu' => $order['nom_menu'],
            'menu_description' => $order['menu_description'],
            'menu_image_url' => $order['menu_image_url'],
            'menu_image_alt' => $order['menu_image_alt'],
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Construit l historique de statut d une commande.
    private function getOrderStatusHistory(Connection $connection, int $orderId, array $order): array
    {
        $history = $connection->fetchAllAssociative(
            'SELECT h.date_changement, h.commentaire, h.statut_id,
                    COALESCE(sc.libelle, "En attente") AS statut_libelle,
                    COALESCE(sc.code, "en_attente") AS statut_code
             FROM historique_statuts_commande h
             LEFT JOIN statuts_commande sc ON sc.statut_id = h.statut_id
             WHERE h.commande_id = ?
             ORDER BY h.date_changement ASC, h.historique_id ASC',
            [$orderId]
        );

        if ((string) ($order['statut_code'] ?? '') === 'annulee') {
            return $history !== [] ? $history : [[
                'date_changement' => $order['date_commande'] ?? null,
                'commentaire' => $order['motif_annulation'] ?? null,
                'statut_libelle' => $order['statut_libelle'] ?? 'Annulée',
                'statut_code' => 'annulee',
            ]];
        }

        $currentStatusId = (int) ($order['statut_id'] ?? 0);
        $statusSteps = [];

        if ($currentStatusId > 0) {
            $statusSteps = $connection->fetchAllAssociative(
                'SELECT statut_id, code AS statut_code, libelle AS statut_libelle, ordre
                 FROM statuts_commande
                 WHERE ordre <= (
                     SELECT ordre FROM statuts_commande WHERE statut_id = ?
                 )
                 ORDER BY ordre ASC, statut_id ASC',
                [$currentStatusId]
            );
        }

        if ($statusSteps === []) {
            return $history;
        }

        $historyByStatusId = [];
        foreach ($history as $step) {
            $historyByStatusId[(int) $step['statut_id']] = $step;
        }

        $timeline = [];
        foreach ($statusSteps as $index => $statusStep) {
            $statusId = (int) $statusStep['statut_id'];
            $historyStep = $historyByStatusId[$statusId] ?? null;

            $timeline[] = [
                'date_changement' => $historyStep['date_changement'] ?? ($index === 0 ? $order['date_commande'] : null),
                'commentaire' => $historyStep['commentaire'] ?? null,
                'statut_libelle' => $statusStep['statut_libelle'],
                'statut_code' => $statusStep['statut_code'],
            ];
        }

        return $timeline;
    }

    // Prepare les donnees necessaires a l affichage du panier.
    private function renderCart(Request $request, Connection $connection): Response
    {
        $cartItems = $this->getCartItems($request);

        if ($cartItems === []) {
            return $this->render('cart/index.html.twig');
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, description, personnes_minimum,
                    prix_par_personne, stock_disponible, image_url, image_alt, conditions, materiel_disponible
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        if ($menus === []) {
            $this->saveCartItems($request, []);

            return $this->render('cart/index.html.twig');
        }

        $menusById = [];
        foreach ($menus as $menu) {
            $menusById[(int) $menu['menu_id']] = $menu;
        }

        $cartItemsDetailed = [];
        $normalizedCartItems = [];
        $prixMenu = 0;
        $reduction = 0;
        $canRequestMaterial = false;

        foreach ($menuIds as $menuId) {
            if (!isset($menusById[$menuId])) {
                continue;
            }

            $menu = $menusById[$menuId];
            $nombrePersonnesMinimum = (int) $menu['personnes_minimum'];
            $nombrePersonnes = max(
                $nombrePersonnesMinimum,
                (int) ($cartItems[$menuId]['nombre_personnes'] ?? $nombrePersonnesMinimum)
            );
            $ligneTotal = (float) $menu['prix_par_personne'] * $nombrePersonnes;
            $ligneReduction = $nombrePersonnes >= $nombrePersonnesMinimum + 5 ? $ligneTotal * 0.10 : 0;
            $prixMenu += $ligneTotal;
            $reduction += $ligneReduction;
            $normalizedCartItems[$menuId] = ['nombre_personnes' => $nombrePersonnes];
            $proposeMateriel = $this->menuProvidesMaterial($menu);
            $canRequestMaterial = $canRequestMaterial || $proposeMateriel;

            $cartItemsDetailed[] = [
                'menu' => $menu,
                'nombrePersonnes' => $nombrePersonnes,
                'nombrePersonnesMinimum' => $nombrePersonnesMinimum,
                'prixLigne' => $ligneTotal,
                'reductionLigne' => $ligneReduction,
                'prixLigneApresReduction' => $ligneTotal - $ligneReduction,
                'proposeMateriel' => $proposeMateriel,
            ];
        }

        if ($cartItemsDetailed === []) {
            $this->saveCartItems($request, []);

            return $this->render('cart/index.html.twig');
        }

        $this->saveCartItems($request, $normalizedCartItems);

        $session = $request->getSession();
        $customer = $session->get('utilisateur', []);
        $isConnected = $this->getUser() !== null || $session->has('utilisateur_id');
        $prixLivraison = $this->calculateDeliveryPrice([
            'adresse_livraison' => (string) ($customer['adresse_postale'] ?? ''),
            'code_postal_livraison' => (string) ($customer['code_postal'] ?? ''),
            'ville_livraison' => (string) ($customer['ville'] ?? ''),
        ]);
        $prixTotal = $prixMenu - $reduction + $prixLivraison;

        if ($request->isMethod('POST') && !$isConnected) {
            $this->addFlash('error', 'Vous devez vous connecter ou creer un compte pour valider votre panier.');

            return $this->redirectToRoute('cart_index');
        }

        return $this->render('cart/show.html.twig', [
            'cartItems' => $cartItemsDetailed,
            'prixMenu' => $prixMenu,
            'reduction' => $reduction,
            'prixLivraison' => $prixLivraison,
            'prixTotal' => $prixTotal,
            'isConnected' => $isConnected,
            'customer' => $customer,
            'canRequestMaterial' => $canRequestMaterial,
        ]);
    }

    // Verifie le token CSRF commun aux actions sensibles du panier.
    private function isValidCartCsrf(Request $request): bool
    {
        return $this->isCsrfTokenValid('cart_action', (string) $request->request->get('_csrf_token'));
    }

    // Renvoie le resume du panier en JSON pour les actions sans rechargement.
    private function jsonCartSummary(Request $request, Connection $connection): JsonResponse
    {
        $cartItems = $this->getCartItems($request);

        if ($cartItems === []) {
            return $this->json([
                'success' => true,
                'isEmpty' => true,
            ]);
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, personnes_minimum, prix_par_personne
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $menusById = [];
        foreach ($menus as $menu) {
            $menusById[(int) $menu['menu_id']] = $menu;
        }

        $items = [];
        $prixMenu = 0;
        $reduction = 0;
        $normalizedCartItems = [];

        foreach ($menuIds as $menuId) {
            if (!isset($menusById[$menuId])) {
                continue;
            }

            $menu = $menusById[$menuId];
            $minimum = (int) $menu['personnes_minimum'];
            $quantity = max($minimum, (int) ($cartItems[$menuId]['nombre_personnes'] ?? $minimum));
            $lineTotal = (float) $menu['prix_par_personne'] * $quantity;
            $lineDiscount = $quantity >= $minimum + 5 ? $lineTotal * 0.10 : 0;
            $prixMenu += $lineTotal;
            $reduction += $lineDiscount;
            $normalizedCartItems[$menuId] = ['nombre_personnes' => $quantity];

            $items[] = [
                'menuId' => $menuId,
                'quantity' => $quantity,
                'minimum' => $minimum,
                'unitPrice' => (float) $menu['prix_par_personne'],
                'lineTotal' => $lineTotal,
                'lineDiscount' => $lineDiscount,
                'lineTotalAfterDiscount' => $lineTotal - $lineDiscount,
            ];
        }

        $this->saveCartItems($request, $normalizedCartItems);

        $prixLivraison = $this->calculateDeliveryPrice([
            'adresse_livraison' => trim((string) $request->request->get('adresse_livraison')),
            'code_postal_livraison' => trim((string) $request->request->get('code_postal_livraison')),
            'ville_livraison' => trim((string) $request->request->get('ville_livraison')),
        ]);

        return $this->json([
            'success' => true,
            'items' => $items,
            'prixMenu' => $prixMenu,
            'reduction' => $reduction,
            'prixLivraison' => $prixLivraison,
            'prixTotal' => $prixMenu - $reduction + $prixLivraison,
        ]);
    }

    // Met a jour les nombres de personnes depuis les champs du panier.
    private function updateCartQuantitiesFromRequest(Request $request, Connection $connection): void
    {
        $cartItems = $this->getCartItems($request);
        $quantities = $request->request->all('quantities');

        if ($cartItems === [] || $quantities === []) {
            return;
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, personnes_minimum
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $minimumsById = [];
        foreach ($menus as $menu) {
            $minimumsById[(int) $menu['menu_id']] = (int) $menu['personnes_minimum'];
        }

        foreach ($cartItems as $menuId => $item) {
            $minimum = $minimumsById[$menuId] ?? 1;
            $requestedQuantity = (int) ($quantities[$menuId] ?? $minimum);
            $cartItems[$menuId]['nombre_personnes'] = max($minimum, $requestedQuantity);
        }

        $this->saveCartItems($request, $cartItems);
    }

    // Calcule les totaux du panier, la reduction et la livraison.
    private function getCartSummary(Request $request, Connection $connection, ?array $deliveryData = null): ?array
    {
        $cartItems = $this->getCartItems($request);

        if ($cartItems === []) {
            return null;
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, personnes_minimum, prix_par_personne, conditions, materiel_disponible
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $menusById = [];
        foreach ($menus as $menu) {
            $menusById[(int) $menu['menu_id']] = $menu;
        }

        $items = [];
        $prixMenu = 0;
        $reduction = 0;

        foreach ($menuIds as $menuId) {
            if (!isset($menusById[$menuId])) {
                continue;
            }

            $menu = $menusById[$menuId];
            $minimum = (int) $menu['personnes_minimum'];
            $quantity = max($minimum, (int) ($cartItems[$menuId]['nombre_personnes'] ?? $minimum));
            $lineTotal = (float) $menu['prix_par_personne'] * $quantity;
            $lineDiscount = $quantity >= $minimum + 5 ? $lineTotal * 0.10 : 0;
            $prixMenu += $lineTotal;
            $reduction += $lineDiscount;

            $items[] = [
                'menu' => $menu,
                'nombrePersonnes' => $quantity,
                'prixLigne' => $lineTotal,
                'reductionLigne' => $lineDiscount,
                'proposeMateriel' => $this->menuProvidesMaterial($menu),
            ];
        }

        $prixLivraison = $this->calculateDeliveryPrice($deliveryData ?? [
            'adresse_livraison' => '',
            'code_postal_livraison' => '',
            'ville_livraison' => '',
        ]);

        return [
            'items' => $items,
            'prixMenu' => $prixMenu,
            'reduction' => $reduction,
            'prixLivraison' => $prixLivraison,
            'prixTotal' => $prixMenu - $reduction + $prixLivraison,
        ];
    }

    // Verifie la colonne materiel_disponible du menu pour savoir si le client peut demander un pret de materiel.
    private function menuProvidesMaterial(array $menu): bool
    {
        return (int) ($menu['materiel_disponible'] ?? 0) === 1;
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    // Verifie que la date choisie respecte le delai minimum indique dans les conditions du menu.
    private function validateMenuLeadTimes(array $items, string $datePrestation): ?string
    {
        try {
            $today = new \DateTimeImmutable('today');
            $deliveryDate = new \DateTimeImmutable($datePrestation);
        } catch (\Throwable) {
            return 'La date de livraison est invalide.';
        }

        $daysBeforeDelivery = (int) $today->diff($deliveryDate)->format('%r%a');

        foreach ($items as $item) {
            $menu = $item['menu'] ?? [];
            $conditions = (string) ($menu['conditions'] ?? '');
            $leadTime = $this->extractLeadTimeFromConditions($conditions);

            if ($leadTime['days'] <= 0) {
                continue;
            }

            if ($daysBeforeDelivery < $leadTime['days']) {
                return sprintf(
                    '%s doit être commandé au minimum %s avant la date de livraison. Veuillez choisir une date plus éloignée.',
                    (string) ($menu['nom_menu'] ?? 'Ce menu'),
                    $leadTime['label']
                );
            }
        }

        return null;
    }

    /**
     * @return array{days: int, label: string}
     */
    // Extrait un delai comme "5 jours", "1 semaine" ou "2 mois" depuis le texte des conditions.
    private function extractLeadTimeFromConditions(string $conditions): array
    {
        $normalized = mb_strtolower($conditions);

        if (preg_match('/minimum\s+(\d+)\s+(jour|jours|journée|journées)/u', $normalized, $matches)) {
            $days = (int) $matches[1];

            return ['days' => $days, 'label' => $days . ' jour' . ($days > 1 ? 's' : '')];
        }

        if (preg_match('/minimum\s+(\d+)\s+(semaine|semaines)/u', $normalized, $matches)) {
            $weeks = (int) $matches[1];

            return ['days' => $weeks * 7, 'label' => $weeks . ' semaine' . ($weeks > 1 ? 's' : '')];
        }

        if (preg_match('/minimum\s+(\d+)\s+(mois)/u', $normalized, $matches)) {
            $months = (int) $matches[1];

            return ['days' => $months * 30, 'label' => $months . ' mois'];
        }

        return ['days' => 0, 'label' => ''];
    }

    /**
     * @param array<string, string> $deliveryData
     */
    // Calcule les frais : gratuit pour Bordeaux 33000, sinon 5 euros + 0,59 euro par kilometre.
    private function calculateDeliveryPrice(array $deliveryData): float
    {
        $postalCode = preg_replace('/\D/', '', (string) ($deliveryData['code_postal_livraison'] ?? ''));

        if ($postalCode === '' || $postalCode === '33000') {
            return 0.0;
        }

        $coordinates = $this->findDeliveryCoordinates($deliveryData);
        $distanceKm = $coordinates
            ? $this->findDrivingDistanceKm($coordinates['lat'], $coordinates['lon'])
            : $this->estimateDistanceFromPostalCode($postalCode);

        return round(self::DELIVERY_BASE_PRICE + ($distanceKm * self::DELIVERY_PRICE_PER_KM), 2);
    }

    /**
     * @param array<string, string> $deliveryData
     * @return array{lat: float, lon: float}|null
     */
    // Essaie de convertir l'adresse de livraison en coordonnees GPS via le service public adresse.data.gouv.fr.
    private function findDeliveryCoordinates(array $deliveryData): ?array
    {
        $query = trim(sprintf(
            '%s %s %s',
            (string) ($deliveryData['adresse_livraison'] ?? ''),
            (string) ($deliveryData['code_postal_livraison'] ?? ''),
            (string) ($deliveryData['ville_livraison'] ?? '')
        ));

        if ($query === '') {
            return null;
        }

        $url = 'https://api-adresse.data.gouv.fr/search/?limit=1&q=' . rawurlencode($query);
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $payload = json_decode($response, true);
        $coordinates = $payload['features'][0]['geometry']['coordinates'] ?? null;

        if (!is_array($coordinates) || !isset($coordinates[0], $coordinates[1])) {
            return null;
        }

        return [
            'lat' => (float) $coordinates[1],
            'lon' => (float) $coordinates[0],
        ];
    }

    // Recupere une distance routiere quand le service public de calcul d itineraire est disponible.
    private function findDrivingDistanceKm(float $endLat, float $endLon): float
    {
        $url = sprintf(
            'https://router.project-osrm.org/route/v1/driving/%F,%F;%F,%F?overview=false',
            self::BORDEAUX_LONGITUDE,
            self::BORDEAUX_LATITUDE,
            $endLon,
            $endLat
        );
        $context = stream_context_create([
            'http' => [
                'timeout' => 2,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return $this->calculateDistanceKm(self::BORDEAUX_LATITUDE, self::BORDEAUX_LONGITUDE, $endLat, $endLon);
        }

        $payload = json_decode($response, true);
        $distanceMeters = $payload['routes'][0]['distance'] ?? null;

        if (!is_numeric($distanceMeters)) {
            return $this->calculateDistanceKm(self::BORDEAUX_LATITUDE, self::BORDEAUX_LONGITUDE, $endLat, $endLon);
        }

        return ((float) $distanceMeters) / 1000;
    }

    // Distance geographique entre Bordeaux et l'adresse de livraison.
    private function calculateDistanceKm(float $startLat, float $startLon, float $endLat, float $endLon): float
    {
        $earthRadiusKm = 6371;
        $latDistance = deg2rad($endLat - $startLat);
        $lonDistance = deg2rad($endLon - $startLon);

        $a = sin($latDistance / 2) ** 2
            + cos(deg2rad($startLat)) * cos(deg2rad($endLat)) * sin($lonDistance / 2) ** 2;

        return $earthRadiusKm * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    // Estimation de secours si l'adresse ne peut pas etre geocodee.
    private function estimateDistanceFromPostalCode(string $postalCode): float
    {
        $knownDistances = [
            '33100' => 5.0,
            '33200' => 4.0,
            '33300' => 4.0,
            '33800' => 3.0,
            '33110' => 4.0,
            '33130' => 4.0,
            '33140' => 7.0,
            '33150' => 5.0,
            '33160' => 13.0,
            '33170' => 8.0,
            '33185' => 8.0,
            '33270' => 5.0,
            '33290' => 10.0,
            '33310' => 6.0,
            '33320' => 8.0,
            '33360' => 9.0,
            '33370' => 8.0,
            '33400' => 4.0,
            '33450' => 14.0,
            '33500' => 35.0,
            '33520' => 6.0,
            '33530' => 8.0,
            '33560' => 9.0,
            '33600' => 7.0,
            '33610' => 15.0,
            '33700' => 7.0,
            '33710' => 30.0,
            '33720' => 35.0,
            '33850' => 15.0,
            '33950' => 55.0,
            '37000' => 300.0,
        ];

        return $knownDistances[$postalCode] ?? 25.0;
    }

    /**
     * @return list<string>
     */
    // Recupere les colonnes d une table pour adapter les insertions SQL.
    private function getTableColumns(Connection $connection, string $tableName): array
    {
        try {
            return array_map(
                static fn (array $column): string => (string) $column['COLUMN_NAME'],
                $connection->fetchAllAssociative(
                    'SELECT COLUMN_NAME
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                    [$tableName]
                )
            );
        } catch (\Throwable) {
            return [];
        }
    }

    // Cree la table des lignes de commande si elle n'existe pas encore.
    private function ensureOrderMenuTable(Connection $connection): void
    {
        $connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS commande_menus (
                commande_menu_id INT AUTO_INCREMENT NOT NULL,
                commande_id INT NOT NULL,
                menu_id INT NOT NULL,
                nombre_personnes INT NOT NULL,
                prix_par_personne DECIMAL(10,2) NOT NULL,
                prix_menu DECIMAL(10,2) NOT NULL,
                reduction DECIMAL(10,2) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL,
                PRIMARY KEY(commande_menu_id),
                INDEX idx_commande_menus_commande (commande_id),
                INDEX idx_commande_menus_menu (menu_id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
        );
    }

    /**
     * @return array<int, array{nombre_personnes: int}>
     */
    // Recupere les menus stockes dans la session panier.
    private function getCartItems(Request $request): array
    {
        $session = $request->getSession();
        $cartItems = $session->get('cart_items', []);
        $legacyMenuId = $session->get('cart_menu_id');

        if ($cartItems === [] && $legacyMenuId) {
            $cartItems[(int) $legacyMenuId] = ['nombre_personnes' => 0];
            $session->remove('cart_menu_id');
        }

        $cleanItems = [];
        foreach ($cartItems as $menuId => $item) {
            if ($item === false || $item === null) {
                continue;
            }

            $cleanItems[(int) $menuId] = [
                'nombre_personnes' => is_array($item) ? (int) ($item['nombre_personnes'] ?? 0) : 0,
            ];
        }

        return $cleanItems;
    }

    /**
     * @param array<int, array{nombre_personnes: int}> $cartItems
     */
    // Sauvegarde le panier dans la session.
    private function saveCartItems(Request $request, array $cartItems): void
    {
        $request->getSession()->set('cart_items', $cartItems);
        $request->getSession()->remove('cart_menu_id');
    }

    // Ajoute un menu au panier en respectant son minimum de personnes.
    private function addMenuToCart(int $id, Request $request, Connection $connection): bool
    {
        $menuExists = (bool) $connection->fetchOne(
            'SELECT 1 FROM menus WHERE menu_id = ? AND actif = 1',
            [$id]
        );

        if (!$menuExists) {
            return false;
        }

        $cartItems = $this->getCartItems($request);
        $cartItems[$id] = $cartItems[$id] ?? ['nombre_personnes' => 0];
        $this->saveCartItems($request, $cartItems);

        return true;
    }

}
