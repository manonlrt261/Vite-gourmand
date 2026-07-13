<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomerController extends AbstractController
{
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
        ]);
    }

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

    private function handleProfileSubmit(Request $request, Connection $connection, array $customer): void
    {
        $userId = (int) $customer['id'];
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

    private function handleReviewSubmit(Request $request, Connection $connection, int $userId): void
    {
        $commandeId = (int) $request->request->get('commande_id');
        $note = (int) $request->request->get('note');
        $commentaire = trim((string) $request->request->get('commentaire'));

        if ($commandeId <= 0 || $note < 1 || $note > 5) {
            $this->addFlash('review_error', 'Veuillez sélectionner une commande et une note entre 1 et 5.');

            return;
        }

        $commandeExists = (bool) $connection->fetchOne(
            'SELECT 1 FROM commandes WHERE commande_id = ? AND utilisateur_id = ?',
            [$commandeId, $userId]
        );

        if (!$commandeExists) {
            $this->addFlash('review_error', 'La commande sélectionnée est introuvable.');

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
    private function getReviewableOrders(Connection $connection, int $userId): array
    {
        return $connection->fetchAllAssociative(
            'SELECT c.commande_id, c.date_commande, m.nom_menu
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             WHERE c.utilisateur_id = ?
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

    /**
     * @return array<string, array<string, mixed>|false>
     */
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

    private function isPasswordValid(string $password, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        return password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }
}
