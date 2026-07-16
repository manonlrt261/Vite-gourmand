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
        $summary = $this->getCartSummary($request, $connection);

        if (!$summary || $summary['items'] === []) {
            $this->addFlash('cart_error', 'Votre panier est vide.');

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

        $connection->beginTransaction();

        try {
            $commandeColumns = $this->getTableColumns($connection, 'commandes');
            $historiqueColumns = $this->getTableColumns($connection, 'historique_statuts_commande');

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
                    'pret_materiel' => 0,
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

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
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
            '<p>Nous avons bien recu votre commande Vite & Gourmand.</p>',
            '<p>Elle est actuellement en attente de validation par notre equipe. Vous trouverez ci-dessous le recapitulatif de votre demande.</p>',
        ];

        foreach ($orders as $order) {
            $subtotal = (float) $order['prix_par_personne'] * (int) $order['nombre_personnes'];
            $discount = $subtotal - (float) $order['prix_menu'];

            $lines[] = '<hr>';
            $lines[] = '<h2>Commande n&deg;' . (int) $order['commande_id'] . '</h2>';
            $lines[] = '<p><strong>Menu :</strong> ' . htmlspecialchars((string) $order['nom_menu'], ENT_QUOTES, 'UTF-8') . '</p>';
            $lines[] = '<h3>Prestation</h3>';
            $lines[] = '<p><strong>Date de prestation :</strong> ' . htmlspecialchars((string) $order['date_prestation'], ENT_QUOTES, 'UTF-8') . '</p>';
            $lines[] = '<p><strong>Heure de livraison :</strong> ' . htmlspecialchars((string) $order['heure_de_livraison'], ENT_QUOTES, 'UTF-8') . '</p>';
            $lines[] = '<p><strong>Adresse :</strong> ' . htmlspecialchars((string) $order['adresse_livraison'], ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars((string) $order['code_postal_livraison'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string) $order['ville_livraison'], ENT_QUOTES, 'UTF-8') . '</p>';
            $lines[] = '<p><strong>Nombre de personnes :</strong> ' . (int) $order['nombre_personnes'] . '</p>';
            $lines[] = '<h3>Detail du menu</h3>';
            foreach (($order['mealItems'] ?? []) as $category => $item) {
                if (!$item) {
                    continue;
                }

                $lines[] = '<p><strong>' . htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') . ' :</strong> ' . htmlspecialchars((string) $item['nom'], ENT_QUOTES, 'UTF-8') . '</p>';
            }
            $lines[] = '<h3>Tarifs</h3>';
            $lines[] = '<p><strong>Prix par personne :</strong> ' . number_format((float) $order['prix_par_personne'], 2, ',', ' ') . ' &euro;</p>';
            $lines[] = '<p><strong>Sous-total :</strong> ' . number_format($subtotal, 2, ',', ' ') . ' &euro;</p>';
            if ($discount > 0) {
                $lines[] = '<p><strong>Reduction :</strong> - ' . number_format($discount, 2, ',', ' ') . ' &euro;</p>';
            }
            $lines[] = '<p><strong>Livraison :</strong> ' . number_format((float) $order['prix_livraison'], 2, ',', ' ') . ' &euro;</p>';
            $lines[] = '<p><strong>Total TTC :</strong> ' . number_format((float) $order['prix_total'], 2, ',', ' ') . ' &euro;</p>';
            $lines[] = '<p><strong>Statut :</strong> ' . htmlspecialchars((string) $order['statut_libelle'], ENT_QUOTES, 'UTF-8') . '</p>';
        }

        $lines[] = '<hr>';
        $lines[] = '<p><strong>Total de votre commande :</strong> ' . number_format($totalOrder, 2, ',', ' ') . ' &euro;</p>';
        $lines[] = '<p>Vous pouvez suivre votre commande depuis votre espace client.</p>';
        $lines[] = '<p>A tres bientot,<br>L equipe Vite & Gourmand</p>';

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
                'statut_libelle' => $order['statut_libelle'] ?? 'Annulee',
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
                    prix_par_personne, stock_disponible, image_url, image_alt
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

            $cartItemsDetailed[] = [
                'menu' => $menu,
                'nombrePersonnes' => $nombrePersonnes,
                'nombrePersonnesMinimum' => $nombrePersonnesMinimum,
                'prixLigne' => $ligneTotal,
                'reductionLigne' => $ligneReduction,
                'prixLigneApresReduction' => $ligneTotal - $ligneReduction,
            ];
        }

        if ($cartItemsDetailed === []) {
            $this->saveCartItems($request, []);

            return $this->render('cart/index.html.twig');
        }

        $this->saveCartItems($request, $normalizedCartItems);

        $prixLivraison = 0;
        $prixTotal = $prixMenu - $reduction + $prixLivraison;
        $session = $request->getSession();
        $customer = $session->get('utilisateur', []);
        $isConnected = $this->getUser() !== null || $session->has('utilisateur_id');

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

        $prixLivraison = 0;

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
    private function getCartSummary(Request $request, Connection $connection): ?array
    {
        $cartItems = $this->getCartItems($request);

        if ($cartItems === []) {
            return null;
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
            ];
        }

        $prixLivraison = 0;

        return [
            'items' => $items,
            'prixMenu' => $prixMenu,
            'reduction' => $reduction,
            'prixLivraison' => $prixLivraison,
            'prixTotal' => $prixMenu - $reduction + $prixLivraison,
        ];
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
