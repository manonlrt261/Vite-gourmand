<?php

namespace App\Controller;

use App\Service\MongoStatsService;
use App\Validator\InputValidator;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Contrôleur de l'espace client : profil, commandes, modifications et avis.
class CustomerController extends AbstractController
{
    // Affiche le tableau de bord avec le profil, les commandes récentes et les avis déposables.
    public function account(Request $request, Connection $connection): Response
    {
        $session = $request->getSession();
        $userId = (int) $session->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_account')]);
        }

        if ($request->isMethod('POST')) {
            // Le jeton CSRF évite qu'un avis soit envoyé depuis une page externe au site.
            if (!$this->isValidCustomerCsrf($request)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json(['success' => false, 'message' => 'Le formulaire a expiré, veuillez réessayer.'], 400);
                }

                $this->addFlash('review_error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->redirectToRoute('customer_account');
            }

            $reviewResult = $this->handleReviewSubmit($request, $connection, $userId);

            // Si le formulaire est envoyé par JavaScript, on répond sans recharger la page.
            if ($request->isXmlHttpRequest()) {
                return $this->json($reviewResult, $reviewResult['success'] ? 200 : 400);
            }

            return $this->redirectToRoute('customer_account');
        }

        return $this->render('customer/account.html.twig', [
            'customer' => $session->get('utilisateur', []),
            'latestOrders' => $this->getLatestOrders($connection, $userId, 3),
            'reviewOrders' => $this->getReviewableOrders($connection, $userId),
        ]);
    }

    // Liste toutes les commandes appartenant au client connecté.
    public function orders(Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_orders')]);
        }

        if ($request->isMethod('POST')) {
            // Protection CSRF du formulaire d'avis ouvert depuis la liste des commandes.
            if (!$this->isValidCustomerCsrf($request)) {
                $this->addFlash('review_error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->redirectToRoute('customer_orders');
            }

            $this->handleReviewSubmit($request, $connection, $userId);

            return $this->redirectToRoute('customer_orders');
        }

        return $this->render('customer/orders.html.twig', [
            'orders' => $this->getLatestOrders($connection, $userId, 50),
        ]);
    }

    // Affiche le détail d'une commande après vérification de son appartenance au client.
    public function orderDetail(int $id, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_order_detail', ['id' => $id])]);
        }

        if ($request->isMethod('POST')) {
            // Protection CSRF du formulaire d'avis disponible dans le détail d'une commande.
            if (!$this->isValidCustomerCsrf($request)) {
                $this->addFlash('review_error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
            }

            $this->handleReviewSubmit($request, $connection, $userId);

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $order = $connection->fetchAssociative(
            'SELECT c.commande_id, c.date_commande, c.date_prestation, c.heure_de_livraison,
                    c.adresse_livraison, c.ville_livraison, c.code_postal_livraison,
                    c.nombre_personnes, c.prix_menu, c.prix_livraison, c.prix_total, c.pret_materiel, c.statut_id,
                    m.menu_id, m.nom_menu, m.description AS menu_description, m.prix_par_personne,
                    m.image_url AS menu_image_url, m.image_alt AS menu_image_alt,
                    COALESCE(sc.libelle, "En attente") AS statut_libelle,
                    COALESCE(sc.code, "en_attente") AS statut_code,
                    (SELECT COUNT(*)
                     FROM avis a
                     WHERE a.commande_id = c.commande_id AND a.utilisateur_id = c.utilisateur_id) AS avis_count
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.commande_id = ? AND c.utilisateur_id = ?',
            [$id, $userId]
        );

        if (!$order) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        $orderMenus = $this->getOrderMenuLines($connection, $order);
        foreach ($orderMenus as $index => $orderMenu) {
            $orderMenus[$index]['mealItems'] = $this->getOrderMealItems($connection, (int) $orderMenu['menu_id']);
        }

        return $this->render('customer/order_detail.html.twig', [
            'order' => $order,
            'orderMenus' => $orderMenus,
            'mealItems' => $this->getOrderMealItems($connection, (int) $order['menu_id']),
            'statusHistory' => $this->getOrderStatusHistory($connection, (int) $order['commande_id'], $order),
            'availableMenus' => $this->getAvailableMenus($connection),
        ]);
    }

    // Modifie les informations de livraison d'une commande encore en attente.
    public function updateOrder(int $id, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_order_detail', ['id' => $id])]);
        }

        // La modification d'une commande est une action sensible : le jeton CSRF est obligatoire.
        if (!$this->isValidCustomerCsrf($request)) {
            $this->addFlash('order_error', 'Le formulaire a expire, veuillez reessayer.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $order = $this->getPendingCustomerOrder($connection, $id, $userId);

        if (!$order) {
            $this->addFlash('order_error', 'Cette commande ne peut plus etre modifiee car elle a deja ete acceptee.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $datePrestation = trim((string) $request->request->get('date_prestation'));
        $heureLivraison = trim((string) $request->request->get('heure_de_livraison'));
        $adresse = trim((string) $request->request->get('adresse_livraison'));
        $ville = trim((string) $request->request->get('ville_livraison'));
        $codePostal = trim((string) $request->request->get('code_postal_livraison'));
        $nombrePersonnes = max((int) $order['personnes_minimum'], (int) $request->request->get('nombre_personnes'));

        if ($datePrestation === '' || $heureLivraison === '' || $adresse === '' || $ville === '' || $codePostal === '') {
            $this->addFlash('order_error', 'Tous les champs de livraison doivent etre renseignes.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        // Vérifie les nouvelles informations de livraison avant de modifier la commande.
        $deliveryError = $this->validateDeliveryData($datePrestation, $heureLivraison, $adresse, $ville, $codePostal);
        if ($deliveryError !== null) {
            $this->addFlash('order_error', $deliveryError);

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $lineTotal = (float) $order['prix_par_personne'] * $nombrePersonnes;
        $discount = $nombrePersonnes >= (int) $order['personnes_minimum'] + 5 ? $lineTotal * 0.10 : 0;
        $deliveryPrice = (float) $order['prix_livraison'];
        $priceMenu = $lineTotal - $discount;

        $connection->update('commandes', [
            'date_prestation' => $datePrestation,
            'heure_de_livraison' => $heureLivraison,
            'adresse_livraison' => $adresse,
            'ville_livraison' => $ville,
            'code_postal_livraison' => $codePostal,
            'nombre_personnes' => $nombrePersonnes,
            'prix_menu' => $priceMenu,
            'prix_total' => $priceMenu + $deliveryPrice,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ], [
            'commande_id' => $id,
            'utilisateur_id' => $userId,
        ]);

        $this->addOrderStatusHistory($connection, $id, (int) $order['statut_id'], 'Commande modifiée par le client.');
        $this->addFlash('order_success', 'Votre commande a bien été modifiée.');

        return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
    }

    // Ajoute un menu disponible à une commande en attente et recalcule ses totaux.
    public function addMenuToOrder(int $id, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_order_detail', ['id' => $id])]);
        }

        // Le jeton CSRF protège l'ajout d'un menu dans une commande existante.
        if (!$this->isValidCustomerCsrf($request)) {
            $this->addFlash('order_error', 'Le formulaire a expire, veuillez reessayer.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $order = $this->getPendingCustomerOrder($connection, $id, $userId);

        if (!$order) {
            $this->addFlash('order_error', 'Vous ne pouvez plus ajouter de menu car cette commande a deja ete acceptee.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $menuId = (int) $request->request->get('menu_id');
        $menu = $connection->fetchAssociative(
            'SELECT menu_id, prix_par_personne, personnes_minimum
             FROM menus
             WHERE menu_id = ? AND actif = 1',
            [$menuId]
        );

        if (!$menu) {
            $this->addFlash('order_error', 'Le menu selectionne est introuvable.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $nombrePersonnes = max((int) $menu['personnes_minimum'], (int) $request->request->get('nombre_personnes'));
        if ($nombrePersonnes < 1 || $nombrePersonnes > 500) {
            $this->addFlash('order_error', 'Le nombre de personnes demande est invalide.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $lineTotal = (float) $menu['prix_par_personne'] * $nombrePersonnes;
        $discount = $nombrePersonnes >= (int) $menu['personnes_minimum'] + 5 ? $lineTotal * 0.10 : 0;
        $priceMenu = $lineTotal - $discount;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $statusId = (int) $order['statut_id'];

        $this->ensureOrderMenuTable($connection);

        $connection->insert('commande_menus', [
            'commande_id' => $id,
            'menu_id' => $menuId,
            'nombre_personnes' => $nombrePersonnes,
            'prix_par_personne' => (float) $menu['prix_par_personne'],
            'prix_menu' => $priceMenu,
            'reduction' => $discount,
            'created_at' => $now,
        ]);

        $totals = $this->getOrderMenuTotals($connection, $id);
        $connection->update('commandes', [
            'nombre_personnes' => $totals['people'],
            'prix_menu' => $totals['price'],
            'prix_total' => $totals['price'] + (float) $order['prix_livraison'],
            'updated_at' => $now,
        ], [
            'commande_id' => $id,
            'utilisateur_id' => $userId,
        ]);

        $this->addOrderStatusHistory($connection, $id, $statusId, 'Menu ajouté par le client depuis le détail de commande.');
        $this->addFlash('order_success', 'Le menu a bien été ajouté à votre commande.');

        return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
    }

    // Supprime une seule ligne de menu d'une commande en attente et recalcule ses totaux.
    public function removeMenuFromOrder(int $id, int $lineId, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_order_detail', ['id' => $id])]);
        }

        if (!$this->isValidCustomerCsrf($request)) {
            $this->addFlash('order_error', 'Le formulaire a expire, veuillez reessayer.');

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $connection->beginTransaction();

        try {
            $order = $connection->fetchAssociative(
                'SELECT c.commande_id, c.statut_id, c.prix_livraison,
                        COALESCE(sc.code, "en_attente") AS statut_code
                 FROM commandes c
                 LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
                 WHERE c.commande_id = ? AND c.utilisateur_id = ?
                 FOR UPDATE',
                [$id, $userId]
            );

            if (!$order) {
                throw new \DomainException('Commande introuvable.');
            }

            if ((string) $order['statut_code'] !== 'en_attente') {
                throw new \DomainException('Cette commande ne peut plus etre modifiee car elle a deja ete acceptee.');
            }

            $lines = $connection->fetchAllAssociative(
                'SELECT commande_menu_id, menu_id
                 FROM commande_menus
                 WHERE commande_id = ?
                 ORDER BY commande_menu_id ASC
                 FOR UPDATE',
                [$id]
            );

            if (count($lines) <= 1) {
                throw new \DomainException('Une commande doit conserver au moins un menu.');
            }

            $lineExists = false;
            foreach ($lines as $line) {
                if ((int) $line['commande_menu_id'] === $lineId) {
                    $lineExists = true;
                    break;
                }
            }

            if (!$lineExists) {
                throw new \DomainException('Le menu selectionne est introuvable dans cette commande.');
            }

            $connection->delete('commande_menus', [
                'commande_menu_id' => $lineId,
                'commande_id' => $id,
            ]);

            $totals = $this->getOrderMenuTotals($connection, $id);
            $remainingMenuId = (int) $connection->fetchOne(
                'SELECT menu_id FROM commande_menus WHERE commande_id = ? ORDER BY commande_menu_id ASC LIMIT 1',
                [$id]
            );

            $connection->update('commandes', [
                'menu_id' => $remainingMenuId,
                'nombre_personnes' => $totals['people'],
                'prix_menu' => $totals['price'],
                'prix_total' => $totals['price'] + (float) $order['prix_livraison'],
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'commande_id' => $id,
                'utilisateur_id' => $userId,
            ]);

            $this->addOrderStatusHistory($connection, $id, (int) $order['statut_id'], 'Menu supprimé par le client depuis le détail de commande.');
            $connection->commit();
        } catch (\DomainException $exception) {
            $connection->rollBack();
            $this->addFlash('order_error', $exception->getMessage());

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        $this->addFlash('order_success', 'Le menu a bien été supprimé de votre commande.');

        return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
    }

    // Annule une commande autorisée, historise le changement et met à jour les statistiques.
    public function cancelOrder(int $id, Request $request, Connection $connection, MongoStatsService $mongoStatsService): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_orders')]);
        }

        // Annuler une commande modifie la base : le jeton CSRF empêche une annulation non voulue.
        if (!$this->isValidCustomerCsrf($request)) {
            $this->addFlash('order_error', 'Le formulaire a expire, veuillez reessayer.');

            return $this->redirectToRoute('customer_orders');
        }

        $order = $connection->fetchAssociative(
            'SELECT c.commande_id, c.statut_id, COALESCE(sc.code, "en_attente") AS statut_code
             FROM commandes c
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.commande_id = ? AND c.utilisateur_id = ?',
            [$id, $userId]
        );

        if (!$order) {
            $this->addFlash('order_error', 'Commande introuvable.');

            return $this->redirectToRoute('customer_orders');
        }

        if ((string) $order['statut_code'] !== 'en_attente') {
            $this->addFlash('order_error', 'Cette commande ne peut plus etre annulee car elle a deja ete acceptee.');

            return $this->redirectToRoute('customer_orders');
        }

        $cancelStatusId = (int) $connection->fetchOne(
            'SELECT statut_id FROM statuts_commande WHERE code = ? LIMIT 1',
            ['annulee']
        );

        if ($cancelStatusId === 0) {
            $this->addFlash('order_error', 'Le statut d annulation est introuvable.');

            return $this->redirectToRoute('customer_orders');
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $connection->update('commandes', [
            'statut_id' => $cancelStatusId,
            'motif_annulation' => 'Annulation demandee par le client depuis son espace personnel.',
            'updated_at' => $now,
        ], [
            'commande_id' => $id,
            'utilisateur_id' => $userId,
        ]);

        $this->addOrderStatusHistory($connection, $id, $cancelStatusId, 'Commande annulée par le client.');
        // La commande reste dans MySQL pour la traçabilité, mais sort immédiatement des statistiques MongoDB.
        $mongoStatsService->getOrdersByMenuDocuments($connection);
        $this->addFlash('order_success', 'Votre commande a bien été annulée.');

        return $this->redirectToRoute('customer_orders');
    }

    // Affiche et traite les modifications du profil du client connecté.
    public function profile(Request $request, Connection $connection): Response
    {
        $session = $request->getSession();
        $userId = (int) $session->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_profile')]);
        }

        $customer = $connection->fetchAssociative(
            'SELECT id, nom, prenom, email, telephone, mot_de_passe, adresse_postale, ville, code_postal, role_id, actif
             FROM utilisateurs
             WHERE id = ?',
            [$userId]
        );

        if (!$customer || (int) $customer['actif'] !== 1) {
            return $this->redirectToRoute('logout');
        }

        if ($request->isMethod('POST')) {
            // Les informations personnelles et le mot de passe sont protégés par un token CSRF.
            if (!$this->isValidCustomerCsrf($request)) {
                $this->addFlash('profile_error', 'Le formulaire a expiré, veuillez réessayer.');

                return $this->redirectToRoute('customer_profile');
            }

            $this->handleProfileSubmit($request, $connection, $customer);

            return $this->redirectToRoute('customer_profile');
        }

        return $this->render('customer/profile.html.twig', [
            'customer' => $customer,
        ]);
    }

    // Vérifie le token CSRF commun aux formulaires de l'espace client.
    private function isValidCustomerCsrf(Request $request): bool
    {
        return $this->isCsrfTokenValid('customer_action', (string) $request->request->get('_csrf_token'));
    }

    // Centralise la mise à jour du profil, de l'adresse e-mail et du mot de passe.
    private function handleProfileSubmit(Request $request, Connection $connection, array $customer): void
    {
        $userId = (int) $customer['id'];
        $profileAction = (string) $request->request->get('profile_action', 'information');

        if ($profileAction === 'password') {
            $passwordData = [
                'current' => (string) $request->request->get('current_password'),
                'new' => (string) $request->request->get('new_password'),
                'confirm' => (string) $request->request->get('new_password_confirm'),
            ];

            if ($passwordData['current'] === '' || $passwordData['new'] === '' || $passwordData['confirm'] === '') {
                $this->addFlash('profile_error', 'Tous les champs du changement de mot de passe doivent être renseignés.');

                return;
            }

            if (!$this->isPasswordValid($passwordData['current'], (string) $customer['mot_de_passe'])) {
                $this->addFlash('profile_error', 'Le mot de passe actuel est incorrect.');

                return;
            }

            if ($passwordData['new'] !== $passwordData['confirm']) {
                $this->addFlash('profile_error', 'Les deux nouveaux mots de passe ne sont pas identiques.');

                return;
            }

            if (!$this->isStrongPassword($passwordData['new'])) {
                $this->addFlash('profile_error', 'Le nouveau mot de passe doit respecter les conditions indiquées.');

                return;
            }

            // Le nouveau mot de passe est haché avant d'être enregistré en base.
            $hashedPassword = password_hash($passwordData['new'], PASSWORD_DEFAULT);
            $updatedRows = $connection->update('utilisateurs', [
                'mot_de_passe' => $hashedPassword,
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], ['id' => $userId]);

            try {
            // Si le compte employé avait un mot de passe initial visible par l'administrateur,
            // il est effacé dès que l'utilisateur définit son propre mot de passe.
                $connection->update('utilisateurs', ['mot_de_passe_initial' => null], ['id' => $userId]);
            } catch (\Throwable) {
            }

            // On relit la base pour confirmer que le nouveau mot de passe a bien remplacé l'ancien.
            $savedPassword = (string) $connection->fetchOne(
                'SELECT mot_de_passe FROM utilisateurs WHERE id = ?',
                [$userId]
            );

            if ($updatedRows < 1 || !password_verify($passwordData['new'], $savedPassword)) {
                $this->addFlash('profile_error', "Le nouveau mot de passe n'a pas pu être enregistré. Veuillez réessayer.");

                return;
            }

            $this->refreshCustomerSession($request, $connection, $userId);
            $this->addFlash('profile_success', 'Votre nouveau mot de passe a bien été enregistré.');

            return;
        }

        $data = [
            'prenom' => trim((string) $request->request->get('prenom')),
            'nom' => trim((string) $request->request->get('nom')),
            'email' => trim((string) $request->request->get('email')),
            'telephone' => trim((string) $request->request->get('telephone')),
            'adresse_postale' => trim((string) $request->request->get('adresse_postale')),
            'code_postal' => trim((string) $request->request->get('code_postal')),
            'ville' => trim((string) $request->request->get('ville')),
        ];

        foreach ($data as $value) {
            if ($value === '') {
                $this->addFlash('profile_error', "Tous les champs d'informations personnelles doivent être renseignés.");

                return;
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('profile_error', 'Veuillez renseigner une adresse email valide.');

            return;
        }

            // Contrôle les informations personnelles modifiées par le client.
        $profileError = $this->validateProfileData($data);
        if ($profileError !== null) {
            $this->addFlash('profile_error', $profileError);

            return;
        }

        $existingUser = $connection->fetchOne(
            'SELECT id FROM utilisateurs WHERE email = ? AND id <> ?',
            [$data['email'], $userId]
        );

        if ($existingUser) {
            $this->addFlash('profile_error', 'Cette adresse email est déjà utilisée par un autre compte.');

            return;
        }

        $updateData = $data;
        $updateData['updated_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $connection->update('utilisateurs', $updateData, ['id' => $userId]);

        $this->refreshCustomerSession($request, $connection, $userId);
        $this->addFlash('profile_success', 'Vos informations ont bien été mises à jour.');
    }

    /**
     * @return array{success: bool, message: string, commande_id?: int}
     */
    // Valide qu'une commande terminée peut recevoir un unique avis du client.
    private function handleReviewSubmit(Request $request, Connection $connection, int $userId): array
    {
        $commandeId = (int) $request->request->get('commande_id');
        $note = (int) $request->request->get('note');
        $commentaire = trim((string) $request->request->get('commentaire'));

        if ($commandeId <= 0 || $note < 1 || $note > 5) {
            $message = 'Veuillez sélectionner une commande et une note entre 1 et 5.';
            $this->addFlash('review_error', $message);

            return ['success' => false, 'message' => $message];
        }

        // Limite la taille du commentaire pour éviter une saisie trop longue en base.
        if (!InputValidator::hasMaxLength($commentaire, 1500)) {
            $message = 'Votre commentaire est trop long.';
            $this->addFlash('review_error', $message);

            return ['success' => false, 'message' => $message];
        }

        $orderStatusCode = $connection->fetchOne(
            'SELECT COALESCE(sc.code, "en_attente")
             FROM commandes c
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.commande_id = ? AND c.utilisateur_id = ?',
            [$commandeId, $userId]
        );

        if (!$orderStatusCode) {
            $message = 'La commande sélectionnée est introuvable.';
            $this->addFlash('review_error', $message);

            return ['success' => false, 'message' => $message];
        }

        if ((string) $orderStatusCode !== 'terminee') {
            $message = 'Vous pourrez laisser un avis lorsque la commande sera terminée.';
            $this->addFlash('review_error', $message);

            return ['success' => false, 'message' => $message];
        }

        $alreadyReviewed = (bool) $connection->fetchOne(
            'SELECT 1 FROM avis WHERE commande_id = ? AND utilisateur_id = ?',
            [$commandeId, $userId]
        );

        if ($alreadyReviewed) {
            $message = 'Vous avez déjà laissé un avis pour cette commande.';
            $this->addFlash('review_error', $message);

            return ['success' => false, 'message' => $message];
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $connection->insert('avis', [
            'commande_id' => $commandeId,
            'utilisateur_id' => $userId,
            'note' => $note,
            'commentaire' => $commentaire !== '' ? $commentaire : null,
            'statut' => 'en_attente',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $message = 'Votre avis a bien été envoyé.';
        $this->addFlash('review_success', $message);

        return ['success' => true, 'message' => $message, 'commande_id' => $commandeId];
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Récupère les dernières commandes à afficher dans l'espace client.
    private function getLatestOrders(Connection $connection, int $userId, int $limit): array
    {
        return $connection->fetchAllAssociative(
            'SELECT c.commande_id, c.date_commande, c.date_prestation, c.prix_total,
                    m.nom_menu,
                    COALESCE(sc.libelle, "En attente") AS statut_libelle,
                    COALESCE(sc.code, "en_attente") AS statut_code,
                    (SELECT COUNT(*)
                     FROM avis a
                     WHERE a.commande_id = c.commande_id AND a.utilisateur_id = c.utilisateur_id) AS avis_count
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.utilisateur_id = ?
             ORDER BY c.date_commande DESC, c.commande_id DESC
             LIMIT ' . $limit,
            [$userId]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Récupère les commandes terminées pouvant recevoir un avis.
    private function getReviewableOrders(Connection $connection, int $userId): array
    {
        return $connection->fetchAllAssociative(
            'SELECT c.commande_id, c.date_commande, m.nom_menu
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.utilisateur_id = ?
               AND COALESCE(sc.code, "en_attente") = "terminee"
               AND NOT EXISTS (
                   SELECT 1
                   FROM avis a
                   WHERE a.commande_id = c.commande_id
                     AND a.utilisateur_id = c.utilisateur_id
               )
             ORDER BY c.date_commande DESC, c.commande_id DESC',
            [$userId]
        );
    }

    // Ne retourne qu'une commande en attente appartenant au client, afin de sécuriser les modifications.
    private function getPendingCustomerOrder(Connection $connection, int $orderId, int $userId): array|false
    {
        $order = $connection->fetchAssociative(
            'SELECT c.commande_id, c.utilisateur_id, c.menu_id, c.date_prestation, c.heure_de_livraison,
                    c.adresse_livraison, c.ville_livraison, c.code_postal_livraison,
                    c.nombre_personnes, c.prix_livraison, c.statut_id,
                    m.prix_par_personne, m.personnes_minimum,
                    COALESCE(sc.code, "en_attente") AS statut_code
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.commande_id = ? AND c.utilisateur_id = ?',
            [$orderId, $userId]
        );

        if (!$order || (string) $order['statut_code'] !== 'en_attente') {
            return false;
        }

        return $order;
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Récupère les menus actifs disponibles pour modifier une commande.
    private function getAvailableMenus(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, personnes_minimum, prix_par_personne
             FROM menus
             WHERE actif = 1
             ORDER BY nom_menu ASC'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Récupère les menus d'une commande tout en restant compatible avec les anciennes commandes à menu unique.
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
            // Si la table commande_menus n'existe pas encore, on affiche l'ancien format.
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
     * @return array{people:int, price:float}
     */
    // Calcule les totaux d'une commande à partir de ses lignes de menus.
    private function getOrderMenuTotals(Connection $connection, int $orderId): array
    {
        $totals = $connection->fetchAssociative(
            'SELECT COALESCE(SUM(nombre_personnes), 0) AS people,
                    COALESCE(SUM(prix_menu), 0) AS price
             FROM commande_menus
             WHERE commande_id = ?',
            [$orderId]
        );

        return [
            'people' => (int) ($totals['people'] ?? 0),
            'price' => (float) ($totals['price'] ?? 0),
        ];
    }

    // Crée la table des lignes de commande si elle n'existe pas encore.
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
     * @return array<string, array<string, mixed>|false>
     */
    // Récupère la composition du menu associé à une commande.
    private function getOrderMealItems(Connection $connection, int $menuId): array
    {
        return [
            'Entrée' => $connection->fetchAssociative(
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
    // Construit le suivi chronologique de la commande.
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

    // Conserve chaque changement de statut dans l'historique visible du client.
    private function addOrderStatusHistory(Connection $connection, int $orderId, int $statusId, string $comment): void
    {
        try {
            $connection->insert('historique_statuts_commande', [
                'commande_id' => $orderId,
                'statut_id' => $statusId,
                'date_changement' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'commentaire' => $comment,
            ]);
        } catch (\Throwable) {
        }
    }

    // Recharge en session les informations de profil après une modification réussie.
    private function refreshCustomerSession(Request $request, Connection $connection, int $userId): void
    {
        $user = $connection->fetchAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.adresse_postale, u.ville, u.code_postal, u.role_id, r.libelle AS role_libelle
             FROM utilisateurs u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE u.id = ?',
            [$userId]
        );

        if (!$user) {
            return;
        }

        $request->getSession()->set('utilisateur', [
            'id' => (int) $user['id'],
            'nom' => $user['nom'],
            'prenom' => $user['prenom'],
            'email' => $user['email'],
            'telephone' => $user['telephone'],
            'adresse_postale' => $user['adresse_postale'],
            'ville' => $user['ville'],
            'code_postal' => $user['code_postal'],
            'role_id' => (int) $user['role_id'],
            'role_libelle' => strtolower((string) ($user['role_libelle'] ?? 'utilisateur')),
        ]);
    }

    // Vérifie le mot de passe saisi avec le mot de passe stocké.
    private function isPasswordValid(string $password, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        return password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
    }

    // Contrôle que le nouveau mot de passe respecte les règles de sécurité.
    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }

    // Valide les champs modifiables d'une commande côté client.
    private function validateDeliveryData(string $datePrestation, string $heureLivraison, string $adresse, string $ville, string $codePostal): ?string
    {
        if (!InputValidator::isFutureOrTodayDate($datePrestation)) {
            return 'La date de prestation doit etre valide et ne peut pas etre dans le passe.';
        }

        if (!InputValidator::isValidTime($heureLivraison)) {
            return 'Veuillez renseigner une heure de livraison valide.';
        }

        if (!InputValidator::isTimeBetween($heureLivraison, '08:00', '22:00')) {
            return 'Les livraisons sont possibles entre 8h00 et 22h00';
        }

        if (!InputValidator::isValidPostalCode($codePostal)) {
            return 'Veuillez renseigner un code postal valide a 5 chiffres.';
        }

        if (
            !InputValidator::hasMaxLength($adresse, 255)
            || !InputValidator::hasMaxLength($ville, 100)
            || !InputValidator::hasMaxLength($codePostal, 10)
        ) {
            return 'Certaines informations de livraison sont trop longues.';
        }

        return null;
    }

    /**
     * @param array<string, string> $data
     */
    // Valide les informations du profil avant leur sauvegarde.
    private function validateProfileData(array $data): ?string
    {
        if (!InputValidator::isValidPhone($data['telephone'] ?? '')) {
            return 'Veuillez renseigner un numero de telephone valide.';
        }

        if (!InputValidator::isValidPostalCode($data['code_postal'] ?? '')) {
            return 'Veuillez renseigner un code postal valide a 5 chiffres.';
        }

        $lengths = [
            'prenom' => 100,
            'nom' => 100,
            'email' => 255,
            'telephone' => 20,
            'adresse_postale' => 255,
            'code_postal' => 10,
            'ville' => 250,
        ];

        foreach ($lengths as $field => $maxLength) {
            if (!InputValidator::hasMaxLength($data[$field] ?? '', $maxLength)) {
                return 'Certaines informations personnelles sont trop longues.';
            }
        }

        return null;
    }
}

