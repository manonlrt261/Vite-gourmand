<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Controleur de l espace client et des commandes client.
class CustomerController extends AbstractController
{
    // Affiche le tableau de bord client avec profil, commandes recentes et avis.
    public function account(Request $request, Connection $connection): Response
    {
        $session = $request->getSession();
        $userId = (int) $session->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_account')]);
        }

        if ($request->isMethod('POST')) {
            $this->handleReviewSubmit($request, $connection, $userId);

            return $this->redirectToRoute('customer_account');
        }

        return $this->render('customer/account.html.twig', [
            'customer' => $session->get('utilisateur', []),
            'latestOrders' => $this->getLatestOrders($connection, $userId, 3),
            'reviewOrders' => $this->getReviewableOrders($connection, $userId),
        ]);
    }

    // Affiche toutes les commandes du client connecte.
    public function orders(Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_orders')]);
        }

        if ($request->isMethod('POST')) {
            $this->handleReviewSubmit($request, $connection, $userId);

            return $this->redirectToRoute('customer_orders');
        }

        return $this->render('customer/orders.html.twig', [
            'orders' => $this->getLatestOrders($connection, $userId, 50),
        ]);
    }

    // Affiche le detail d une commande appartenant au client.
    public function orderDetail(int $id, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_order_detail', ['id' => $id])]);
        }

        if ($request->isMethod('POST')) {
            $this->handleReviewSubmit($request, $connection, $userId);

            return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
        }

        $order = $connection->fetchAssociative(
            'SELECT c.commande_id, c.date_commande, c.date_prestation, c.heure_de_livraison,
                    c.adresse_livraison, c.ville_livraison, c.code_postal_livraison,
                    c.nombre_personnes, c.prix_menu, c.prix_livraison, c.prix_total, c.statut_id,
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

        return $this->render('customer/order_detail.html.twig', [
            'order' => $order,
            'mealItems' => $this->getOrderMealItems($connection, (int) $order['menu_id']),
            'statusHistory' => $this->getOrderStatusHistory($connection, (int) $order['commande_id'], $order),
            'availableMenus' => $this->getAvailableMenus($connection),
        ]);
    }

    // Permet au client de modifier une commande encore en attente.
    public function updateOrder(int $id, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_order_detail', ['id' => $id])]);
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

        $this->addOrderStatusHistory($connection, $id, (int) $order['statut_id'], 'Commande modifiee par le client.');
        $this->addFlash('order_success', 'Votre commande a bien ete modifiee.');

        return $this->redirectToRoute('customer_order_detail', ['id' => $id]);
    }

    // Ajoute un menu a une commande client encore modifiable.
    public function addMenuToOrder(int $id, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_order_detail', ['id' => $id])]);
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
        $lineTotal = (float) $menu['prix_par_personne'] * $nombrePersonnes;
        $discount = $nombrePersonnes >= (int) $menu['personnes_minimum'] + 5 ? $lineTotal * 0.10 : 0;
        $priceMenu = $lineTotal - $discount;
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $statusId = (int) $order['statut_id'];

        $connection->insert('commandes', [
            'utilisateur_id' => $userId,
            'menu_id' => $menuId,
            'date_commande' => $now,
            'date_prestation' => $order['date_prestation'],
            'heure_de_livraison' => $order['heure_de_livraison'],
            'adresse_livraison' => $order['adresse_livraison'],
            'ville_livraison' => $order['ville_livraison'],
            'code_postal_livraison' => $order['code_postal_livraison'],
            'nombre_personnes' => $nombrePersonnes,
            'prix_menu' => $priceMenu,
            'prix_livraison' => 0,
            'prix_total' => $priceMenu,
            'pret_materiel' => 0,
            'motif_annulation' => '',
            'created_at' => $now,
            'updated_at' => $now,
            'statut_id' => $statusId,
        ]);

        $newOrderId = (int) $connection->lastInsertId();
        $this->addOrderStatusHistory($connection, $newOrderId, $statusId, 'Menu ajoute par le client depuis le detail de commande.');
        $this->addFlash('order_success', 'Le menu a bien ete ajoute a vos commandes.');

        return $this->redirectToRoute('customer_order_detail', ['id' => $newOrderId]);
    }

    // Permet au client d annuler une commande tant qu elle est en attente.
    public function cancelOrder(int $id, Request $request, Connection $connection): Response
    {
        $userId = (int) $request->getSession()->get('utilisateur_id');

        if ($userId <= 0) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('customer_orders')]);
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

        $this->addOrderStatusHistory($connection, $id, $cancelStatusId, 'Commande annulee par le client.');
        $this->addFlash('order_success', 'Votre commande a bien ete annulee.');

        return $this->redirectToRoute('customer_orders');
    }

    // Affiche et traite la page Mes informations du client.
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
            $this->handleProfileSubmit($request, $connection, $customer);

            return $this->redirectToRoute('customer_profile');
        }

        return $this->render('customer/profile.html.twig', [
            'customer' => $customer,
        ]);
    }

    // Enregistre les modifications des informations personnelles ou du mot de passe.
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
                $this->addFlash('profile_error', 'Tous les champs du changement de mot de passe doivent etre renseignes.');

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
                $this->addFlash('profile_error', 'Le nouveau mot de passe doit respecter les conditions indiquees.');

                return;
            }

            $connection->update('utilisateurs', [
                'mot_de_passe' => password_hash($passwordData['new'], PASSWORD_DEFAULT),
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], ['id' => $userId]);

            $this->addFlash('profile_success', 'Votre nouveau mot de passe a bien ete enregistre.');

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
                $this->addFlash('profile_error', 'Tous les champs d’informations personnelles doivent être renseignés.');

                return;
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('profile_error', 'Veuillez renseigner une adresse email valide.');

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

        $passwordData = [
            'current' => (string) $request->request->get('current_password'),
            'new' => (string) $request->request->get('new_password'),
            'confirm' => (string) $request->request->get('new_password_confirm'),
        ];
        $wantsPasswordChange = $passwordData['current'] !== '' || $passwordData['new'] !== '' || $passwordData['confirm'] !== '';

        $updateData = $data;
        $updateData['updated_at'] = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($wantsPasswordChange) {
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

            $updateData['mot_de_passe'] = password_hash($passwordData['new'], PASSWORD_DEFAULT);
        }

        $connection->update('utilisateurs', $updateData, ['id' => $userId]);

        $this->refreshCustomerSession($request, $connection, $userId);
        $this->addFlash('profile_success', 'Vos informations ont bien été mises à jour.');
    }

    // Enregistre un avis client pour une commande terminee.
    private function handleReviewSubmit(Request $request, Connection $connection, int $userId): void
    {
        $commandeId = (int) $request->request->get('commande_id');
        $note = (int) $request->request->get('note');
        $commentaire = trim((string) $request->request->get('commentaire'));

        if ($commandeId <= 0 || $note < 1 || $note > 5) {
            $this->addFlash('review_error', 'Veuillez sélectionner une commande et une note entre 1 et 5.');

            return;
        }

        $orderStatusCode = $connection->fetchOne(
            'SELECT COALESCE(sc.code, "en_attente")
             FROM commandes c
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.commande_id = ? AND c.utilisateur_id = ?',
            [$commandeId, $userId]
        );

        if (!$orderStatusCode) {
            $this->addFlash('review_error', 'La commande sélectionnée est introuvable.');

            return;
        }

        if ((string) $orderStatusCode !== 'terminee') {
            $this->addFlash('review_error', 'Vous pourrez laisser un avis lorsque la commande sera terminee.');

            return;
        }

        $alreadyReviewed = (bool) $connection->fetchOne(
            'SELECT 1 FROM avis WHERE commande_id = ? AND utilisateur_id = ?',
            [$commandeId, $userId]
        );

        if ($alreadyReviewed) {
            $this->addFlash('review_error', 'Vous avez déjà laissé un avis pour cette commande.');

            return;
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

        $this->addFlash('review_success', 'Votre avis a bien été envoyé.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Recupere les dernieres commandes a afficher dans l espace client.
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
    // Recupere les commandes terminees pouvant recevoir un avis.
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

    // Recupere une commande du client seulement si elle est encore en attente.
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
    // Recupere les menus actifs disponibles pour modification de commande.
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
     * @return array<string, array<string, mixed>|false>
     */
    // Recupere la composition du menu associe a une commande.
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

    // Ajoute une ligne dans l historique des statuts de commande.
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

    // Met a jour les informations du client stockees en session.
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

    // Verifie le mot de passe saisi avec le mot de passe stocke.
    private function isPasswordValid(string $password, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        return password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
    }

    // Controle que le nouveau mot de passe respecte les regles de securite.
    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }
}
