<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmployeeController extends AbstractController
{
    public function dashboard(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        return $this->render('employee/dashboard.html.twig', [
            'orders' => $this->getDashboardOrders($connection),
            'statuses' => $this->getOrderStatuses($connection),
            'pendingReviews' => $this->getPendingReviews($connection),
        ]);
    }

    public function orders(Request $request, Connection $connection, MailerInterface $mailer): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $this->ensureOrderStatuses($connection);
        $this->completeDeliveredOrders($connection, $mailer);

        return $this->render('employee/orders.html.twig', [
            'orders' => $this->getEmployeeOrders($connection),
            'statuses' => $this->getOrderStatuses($connection),
        ]);
    }

    public function advanceOrderStatus(int $id, Request $request, Connection $connection, MailerInterface $mailer): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->json(['success' => false], 403);
        }

        $this->ensureOrderStatuses($connection);

        $order = $connection->fetchAssociative(
            'SELECT c.commande_id, c.statut_id, COALESCE(sc.code, "en_attente") AS statut_code
             FROM commandes c
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE c.commande_id = ?',
            [$id]
        );

        if (!$order) {
            return $this->json(['success' => false], 404);
        }

        $nextCode = match ((string) $order['statut_code']) {
            'en_attente' => 'acceptee',
            'acceptee' => 'en_preparation',
            'en_preparation' => 'en_livraison',
            'en_livraison' => 'terminee',
            default => null,
        };

        if ($nextCode === null) {
            return $this->json([
                'success' => false,
                'message' => 'Ce statut ne peut pas avancer manuellement.',
            ], 409);
        }

        $nextStatus = $connection->fetchAssociative(
            'SELECT statut_id, code, libelle FROM statuts_commande WHERE code = ? LIMIT 1',
            [$nextCode]
        );

        if (!$nextStatus) {
            return $this->json(['success' => false], 404);
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $connection->update('commandes', [
            'statut_id' => (int) $nextStatus['statut_id'],
            'updated_at' => $now,
        ], [
            'commande_id' => $id,
        ]);

        $this->addOrderStatusHistory($connection, $id, (int) $nextStatus['statut_id'], 'Statut mis a jour par un employe.');

        if ((string) $nextStatus['code'] === 'terminee') {
            $this->sendReviewRequestEmail($connection, $mailer, $id);
            $this->sendMaterialReturnReminderEmail($connection, $mailer, $id);
        }

        return $this->json([
            'success' => true,
            'status' => $nextStatus['code'],
            'label' => $nextStatus['libelle'],
            'className' => $this->getOrderStatusClass((string) $nextStatus['code']),
        ]);
    }

    public function menus(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        return $this->render('employee/menus.html.twig', [
            'menus' => $this->getPopularMenus($connection),
            'entrees' => $this->getPopularMealItems($connection, 'entree'),
            'plats' => $this->getPopularMealItems($connection, 'plat'),
            'desserts' => $this->getPopularMealItems($connection, 'dessert'),
        ]);
    }

    public function allMenusAndMeals(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        return $this->render('employee/items_all.html.twig', [
            'menus' => $this->getAllMenus($connection),
            'entrees' => $this->getAllMealItems($connection, 'entree'),
            'plats' => $this->getAllMealItems($connection, 'plat'),
            'desserts' => $this->getAllMealItems($connection, 'dessert'),
        ]);
    }

    public function createMenu(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        if ($request->isMethod('POST')) {
            $connection->insert('menus', $this->getMenuPayload($request));

            return $this->redirectToRoute('employee_items_all');
        }

        return $this->render('employee/item_form.html.twig', $this->getFormViewData('menu', null, true, $connection));
    }

    public function createMealItem(string $type, Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $config = $this->getItemConfig($type);
        if (!$config || $type === 'menu') {
            throw $this->createNotFoundException('Element introuvable.');
        }

        if ($request->isMethod('POST')) {
            $payload = $this->getMealPayload($request);
            $payload['actif'] = $this->resolveMealItemStatus($connection, $payload);
            $connection->insert($config['table'], $payload);

            return $this->redirectToRoute('employee_items_all');
        }

        return $this->render('employee/item_form.html.twig', $this->getFormViewData($type, null, true, $connection));
    }

    public function editItem(string $type, int $id, Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $config = $this->getItemConfig($type);
        if (!$config) {
            throw $this->createNotFoundException('Element introuvable.');
        }

        $item = $connection->fetchAssociative(
            sprintf('SELECT * FROM %s WHERE %s = ?', $config['table'], $config['id']),
            [$id]
        );

        if (!$item) {
            throw $this->createNotFoundException('Element introuvable.');
        }

        if ($request->isMethod('POST')) {
            $payload = $type === 'menu' ? $this->getMenuPayload($request) : $this->getMealPayload($request);
            if ($type !== 'menu') {
                $payload['actif'] = $this->resolveMealItemStatus($connection, $payload);
            }

            $connection->update($config['table'], $payload, [$config['id'] => $id]);
            if ($type === 'menu') {
                $this->syncMealItemsWithMenuStatus($connection, $id, (int) $payload['actif']);
            }

            return $this->redirectToRoute('employee_items_all');
        }

        return $this->render('employee/item_form.html.twig', $this->getFormViewData($type, $item, false, $connection));
    }

    public function toggleItem(string $type, int $id, Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $config = $this->getItemConfig($type);
        if (!$config) {
            throw $this->createNotFoundException('Element introuvable.');
        }

        $currentStatus = $connection->fetchOne(
            sprintf('SELECT actif FROM %s WHERE %s = ?', $config['table'], $config['id']),
            [$id]
        );

        if ($currentStatus !== false) {
            $newStatus = (int) $currentStatus === 1 ? 0 : 1;

            if ($type !== 'menu' && $newStatus === 1 && !$this->canActivateMealItem($connection, $config, $id)) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Impossible de rendre cet élément actif car son menu est inactif.',
                    ], 409);
                }

                return $this->redirectToRoute('employee_items_all');
            }

            $connection->update($config['table'], [
                'actif' => $newStatus,
            ], [
                $config['id'] => $id,
            ]);

            if ($type === 'menu') {
                $this->syncMealItemsWithMenuStatus($connection, $id, $newStatus);
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'type' => $type,
                    'id' => $id,
                    'active' => $newStatus === 1,
                    'label' => $newStatus === 1 ? 'Actif' : 'Inactif',
                    'affectedMenuId' => $type === 'menu' ? $id : null,
                ]);
            }
        }

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => false], 404);
        }

        return $this->redirectToRoute('employee_menus');
    }

    public function deleteItem(string $type, int $id, Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $config = $this->getItemConfig($type);
        if (!$config) {
            throw $this->createNotFoundException('Element introuvable.');
        }

        $connection->update($config['table'], ['actif' => 0], [$config['id'] => $id]);

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        return $this->redirectToRoute('employee_menus');
    }

    public function hours(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $this->ensureScheduleTables($connection);

        if ($request->isMethod('POST')) {
            $action = (string) $request->request->get('action');

            if ($action === 'save_hours') {
                $this->saveWeeklyHours($request, $connection);
            }

            if ($action === 'add_closure') {
                $this->addExceptionalClosure($request, $connection);
            }

            return $this->redirectToRoute('employee_hours');
        }

        return $this->render('employee/hours.html.twig', [
            'weeklyHours' => $this->getWeeklyHours($connection),
            'closures' => $this->getExceptionalClosures($connection),
        ]);
    }

    public function deleteClosure(int $id, Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $this->ensureScheduleTables($connection);
        $connection->delete('fermetures_exceptionnelles', ['fermeture_id' => $id]);

        return $this->redirectToRoute('employee_hours');
    }

    public function reviews(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $this->ensureReviewManagementColumns($connection);

        return $this->render('employee/reviews.html.twig', [
            'reviews' => $this->getReviews($connection, ['en_attente']),
            'menus' => $this->getMenuChoices($connection),
            'pendingCount' => $this->countReviewsByStatus($connection, 'en_attente'),
        ]);
    }

    public function allReviews(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $this->ensureReviewManagementColumns($connection);

        return $this->render('employee/reviews_all.html.twig', [
            'reviews' => $this->getReviews($connection),
            'menus' => $this->getMenuChoices($connection),
        ]);
    }

    public function updateReviewStatus(int $id, Request $request, Connection $connection): Response
    {
        if (!$this->canAccessEmployeeSpace($request, $connection)) {
            return $this->redirectToEmployeeLogin($request);
        }

        $action = (string) $request->request->get('action');
        $newStatus = match ($action) {
            'accept' => 'valide',
            'refuse' => 'refuse',
            'pending' => 'en_attente',
            default => null,
        };

        if ($newStatus !== null) {
            $payload = [
                'statut' => $newStatus,
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];

            if ($newStatus === 'refuse') {
                $this->ensureReviewManagementColumns($connection);
                $payload['afficher_accueil'] = 0;
            }

            $connection->update('avis', $payload, [
                'avis_id' => $id,
            ]);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'status' => $newStatus,
                    'label' => $this->getReviewStatusLabel($newStatus),
                ]);
            }
        }

        if ($action === 'toggle_home') {
            $this->ensureReviewManagementColumns($connection);
            $current = (int) $connection->fetchOne('SELECT afficher_accueil FROM avis WHERE avis_id = ?', [$id]);
            $newValue = $current === 1 ? 0 : 1;

            $connection->update('avis', [
                'afficher_accueil' => $newValue,
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'avis_id' => $id,
            ]);

            if ($newValue === 1) {
                $this->limitHomeReviews($connection);
            }

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'home' => $newValue === 1,
                    'label' => $newValue === 1 ? 'Retirer de l’accueil' : 'Afficher sur l’accueil',
                ]);
            }
        }

        return $this->redirectToRoute('employee_dashboard');
    }

    private function canAccessEmployeeSpace(Request $request, Connection $connection): bool
    {
        $user = $request->getSession()->get('utilisateur');
        $role = is_array($user) ? (string) ($user['role_libelle'] ?? '') : '';
        $roleId = is_array($user) ? (int) ($user['role_id'] ?? 0) : 0;
        $userId = is_array($user) ? (int) ($user['id'] ?? 0) : 0;
        $isActive = is_array($user) ? (int) ($user['actif'] ?? 1) === 1 : false;

        if ($userId > 0 && $roleId !== 3 && $role !== 'administrateur') {
            $isActive = (int) $connection->fetchOne('SELECT actif FROM utilisateurs WHERE id = ?', [$userId]) === 1;
        }

        return $isActive && (in_array($role, ['employe', 'employé', 'administrateur'], true) || in_array($roleId, [2, 3], true));
    }

    private function redirectToEmployeeLogin(Request $request): Response
    {
        if (!$request->getSession()->get('utilisateur_id')) {
            return $this->redirectToRoute('login', ['target' => $request->getPathInfo()]);
        }

        return $this->redirectToRoute('customer_account');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getDashboardOrders(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT c.commande_id, c.date_commande, c.date_prestation, c.prix_total,
                    u.nom, u.prenom,
                    m.nom_menu,
                    COALESCE(sc.code, "en_attente") AS statut_code,
                    COALESCE(sc.libelle, "En attente") AS statut_libelle
             FROM commandes c
             LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             ORDER BY c.date_commande DESC, c.commande_id DESC
             LIMIT 100'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getOrderStatuses(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT statut_id, code, libelle
             FROM statuts_commande
             ORDER BY ordre ASC, statut_id ASC'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getEmployeeOrders(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT c.commande_id, c.date_commande, c.date_prestation, c.heure_de_livraison,
                    c.adresse_livraison, c.ville_livraison, c.code_postal_livraison,
                    c.nombre_personnes, c.prix_total,
                    u.nom, u.prenom,
                    m.nom_menu,
                    COALESCE(sc.code, "en_attente") AS statut_code,
                    COALESCE(sc.libelle, "En attente") AS statut_libelle
             FROM commandes c
             LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             ORDER BY c.date_commande DESC, c.commande_id DESC'
        );
    }

    private function ensureOrderStatuses(Connection $connection): void
    {
        $statuses = [
            ['en_attente', 'En attente', 1],
            ['acceptee', 'Validée', 2],
            ['en_preparation', 'En cours de préparation', 3],
            ['en_livraison', 'En cours de livraison', 4],
            ['livree', 'Livrée', 5],
            ['en_attente_retour_materiel', 'En attente retour matériel', 6],
            ['terminee', 'Terminée', 7],
            ['annulee', 'Annulée', 8],
        ];

        foreach ($statuses as [$code, $label, $order]) {
            $existingId = $connection->fetchOne('SELECT statut_id FROM statuts_commande WHERE code = ? LIMIT 1', [$code]);

            if ($existingId) {
                $connection->update('statuts_commande', [
                    'libelle' => $label,
                    'ordre' => $order,
                ], [
                    'statut_id' => (int) $existingId,
                ]);
                continue;
            }

            $connection->insert('statuts_commande', [
                'code' => $code,
                'libelle' => $label,
                'ordre' => $order,
            ]);
        }
    }

    private function completeDeliveredOrders(Connection $connection, MailerInterface $mailer): void
    {
        $termineeId = (int) $connection->fetchOne('SELECT statut_id FROM statuts_commande WHERE code = ? LIMIT 1', ['terminee']);
        if ($termineeId === 0) {
            return;
        }

        $ordersToComplete = $connection->fetchAllAssociative(
            'SELECT c.commande_id
             FROM commandes c
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE sc.code = ?
               AND c.date_prestation IS NOT NULL
               AND c.heure_de_livraison IS NOT NULL
               AND TIMESTAMP(c.date_prestation, c.heure_de_livraison) <= NOW()',
            ['en_livraison']
        );

        foreach ($ordersToComplete as $order) {
            $orderId = (int) $order['commande_id'];
            $connection->update('commandes', [
                'statut_id' => $termineeId,
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'commande_id' => $orderId,
            ]);

            $this->addOrderStatusHistory($connection, $orderId, $termineeId, 'Commande terminee automatiquement apres livraison.');
            $this->sendReviewRequestEmail($connection, $mailer, $orderId);
            $this->sendMaterialReturnReminderEmail($connection, $mailer, $orderId);
        }
    }

    // Email 5 : rappelle au client de rendre le materiel prete quand une commande avec materiel est terminee.
    private function sendMaterialReturnReminderEmail(Connection $connection, MailerInterface $mailer, int $orderId): void
    {
        $this->ensureMaterialReturnEmailLogTable($connection);

        $alreadySent = (bool) $connection->fetchOne(
            'SELECT 1 FROM materiel_email_log WHERE commande_id = ? LIMIT 1',
            [$orderId]
        );

        if ($alreadySent) {
            return;
        }

        $order = $connection->fetchAssociative(
            'SELECT c.commande_id, c.date_prestation, c.heure_de_livraison, c.adresse_livraison,
                    c.code_postal_livraison, c.ville_livraison, c.pret_materiel,
                    u.email, u.prenom, u.nom,
                    m.nom_menu
             FROM commandes c
             LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             WHERE c.commande_id = ?',
            [$orderId]
        );

        if (!$order || (int) ($order['pret_materiel'] ?? 0) !== 1) {
            return;
        }

        $to = (string) ($order['email'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';
        $firstName = htmlspecialchars((string) ($order['prenom'] ?? ''), ENT_QUOTES, 'UTF-8');
        $menuName = htmlspecialchars((string) ($order['nom_menu'] ?? 'votre menu'), ENT_QUOTES, 'UTF-8');
        $returnDate = (new \DateTimeImmutable('+7 days'))->format('d/m/Y');

        $lines = [
            '<h1>Rappel de retour de materiel</h1>',
            '<p>Bonjour ' . $firstName . ',</p>',
            '<p>Votre commande n&deg;' . (int) $order['commande_id'] . ' est maintenant terminee.</p>',
            '<p>Cette prestation incluait du materiel prete par Vite & Gourmand pour le menu <strong>' . $menuName . '</strong>.</p>',
            '<p>Nous vous rappelons que le materiel doit etre retourne propre, complet et en bon etat.</p>',
            '<p><strong>Date de retour conseillee :</strong> au plus tard le ' . $returnDate . '.</p>',
            '<p>En cas de casse, de perte ou de retard important, des frais supplementaires pourront etre appliques selon les conditions de prestation.</p>',
            '<p>Si vous avez deja rendu le materiel, vous pouvez ne pas tenir compte de ce message.</p>',
            '<p>Merci pour votre confiance,<br>L equipe Vite & Gourmand</p>',
        ];

        try {
            // La table materiel_email_log evite d'envoyer plusieurs rappels pour la meme commande.
            $mailer->send((new Email())
                ->from($from)
                ->to($to)
                ->subject('Retour du materiel prete - Vite & Gourmand')
                ->html(implode("\n", $lines)));

            $connection->insert('materiel_email_log', [
                'commande_id' => $orderId,
                'email' => $to,
                'sent_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Le changement de statut ne doit pas etre bloque si l email ne peut pas partir.
        }
    }

    private function ensureMaterialReturnEmailLogTable(Connection $connection): void
    {
        try {
            $connection->executeStatement(
                'CREATE TABLE IF NOT EXISTS materiel_email_log (
                    log_id INT AUTO_INCREMENT NOT NULL,
                    commande_id INT NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    sent_at DATETIME NOT NULL,
                    PRIMARY KEY(log_id),
                    UNIQUE INDEX UNIQ_MATERIEL_EMAIL_COMMANDE (commande_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
            );
        } catch (\Throwable) {
        }
    }

    // Email 6 : invite le client a laisser un avis des que sa commande passe au statut terminee.
    private function sendReviewRequestEmail(Connection $connection, MailerInterface $mailer, int $orderId): void
    {
        $this->ensureReviewEmailLogTable($connection);

        $alreadySent = (bool) $connection->fetchOne(
            'SELECT 1 FROM avis_email_log WHERE commande_id = ? LIMIT 1',
            [$orderId]
        );

        if ($alreadySent) {
            return;
        }

        $order = $connection->fetchAssociative(
            'SELECT c.commande_id, c.utilisateur_id, u.email, u.prenom, u.nom, m.nom_menu
             FROM commandes c
             LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             WHERE c.commande_id = ?',
            [$orderId]
        );

        if (!$order || !filter_var((string) ($order['email'] ?? ''), FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $reviewAlreadyExists = (bool) $connection->fetchOne(
            'SELECT 1 FROM avis WHERE commande_id = ? AND utilisateur_id = ? LIMIT 1',
            [$orderId, (int) $order['utilisateur_id']]
        );

        if ($reviewAlreadyExists) {
            return;
        }

        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';
        $to = (string) $order['email'];
        $firstName = trim((string) ($order['prenom'] ?? ''));
        $greeting = $firstName !== '' ? 'Bonjour ' . htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') . ',' : 'Bonjour,';
        $menuName = htmlspecialchars((string) ($order['nom_menu'] ?? 'votre menu'), ENT_QUOTES, 'UTF-8');
        $reviewUrl = $this->generateUrl('customer_account', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            // La table avis_email_log evite d'envoyer deux fois l'invitation pour la meme commande.
            $mailer->send((new Email())
                ->from($from)
                ->to($to)
                ->subject('Votre avis nous interesse - Vite & Gourmand')
                ->html(sprintf(
                    '<h1>Votre avis compte beaucoup pour nous</h1>
                    <p>%s</p>
                    <p>Votre commande n&deg;%d est maintenant termin&eacute;e.</p>
                    <p>Nous esp&eacute;rons que le menu <strong>%s</strong> a contribu&eacute; &agrave; rendre votre &eacute;v&eacute;nement gourmand et agr&eacute;able.</p>
                    <p>Vous pouvez laisser un avis depuis votre espace client. Cela aide les futurs clients &agrave; choisir leur menu et nous permet d am&eacute;liorer continuellement notre service.</p>
                    <p><a href="%s">Laisser mon avis</a></p>
                    <p>Merci pour votre confiance.</p>
                    <p>L equipe Vite & Gourmand</p>',
                    $greeting,
                    (int) $order['commande_id'],
                    $menuName,
                    htmlspecialchars($reviewUrl, ENT_QUOTES, 'UTF-8')
                )));

            $connection->insert('avis_email_log', [
                'commande_id' => $orderId,
                'email' => $to,
                'sent_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Le changement de statut ne doit pas etre bloque si l email ne peut pas partir.
        }
    }

    private function ensureReviewEmailLogTable(Connection $connection): void
    {
        try {
            $connection->executeStatement(
                'CREATE TABLE IF NOT EXISTS avis_email_log (
                    log_id INT AUTO_INCREMENT NOT NULL,
                    commande_id INT NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    sent_at DATETIME NOT NULL,
                    PRIMARY KEY(log_id),
                    UNIQUE INDEX UNIQ_AVIS_EMAIL_COMMANDE (commande_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB'
            );
        } catch (\Throwable) {
        }
    }

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

    private function getOrderStatusClass(string $statusCode): string
    {
        return match ($statusCode) {
            'terminee', 'livree' => 'badgeterminee',
            'en_attente' => 'badgeattente',
            default => 'badgevalidee',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getPendingReviews(Connection $connection): array
    {
        $this->ensureReviewManagementColumns($connection);

        return $connection->fetchAllAssociative(
            'SELECT a.avis_id, a.commande_id, a.note, a.commentaire, a.created_at, a.afficher_accueil,
                    u.nom, u.prenom
             FROM avis a
             LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
             WHERE a.statut = ?
             ORDER BY a.created_at DESC, a.avis_id DESC
             LIMIT 2',
            ['en_attente']
        );
    }

    private function ensureReviewManagementColumns(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE avis ADD COLUMN afficher_accueil TINYINT(1) NOT NULL DEFAULT 0');
        } catch (\Throwable) {
        }
    }

    /**
     * @param list<string>|null $statuses
     * @return list<array<string, mixed>>
     */
    private function getReviews(Connection $connection, ?array $statuses = null): array
    {
        $sql = 'SELECT a.avis_id, a.commande_id, a.utilisateur_id, a.note, a.commentaire,
                       a.statut, a.created_at, a.updated_at, a.afficher_accueil,
                       u.nom, u.prenom,
                       c.date_commande, c.menu_id,
                       m.nom_menu
                FROM avis a
                LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
                LEFT JOIN commandes c ON c.commande_id = a.commande_id
                LEFT JOIN menus m ON m.menu_id = c.menu_id';
        $params = [];

        if ($statuses !== null) {
            $placeholders = implode(', ', array_fill(0, count($statuses), '?'));
            $sql .= sprintf(' WHERE a.statut IN (%s)', $placeholders);
            $params = $statuses;
        }

        $sql .= ' ORDER BY a.created_at DESC, a.avis_id DESC';

        return $connection->fetchAllAssociative($sql, $params);
    }

    private function countReviewsByStatus(Connection $connection, string $status): int
    {
        return (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM avis WHERE statut = ?',
            [$status]
        );
    }

    private function getReviewStatusLabel(string $status): string
    {
        return match ($status) {
            'valide' => 'Accepté',
            'refuse' => 'Refusé',
            default => 'En attente',
        };
    }

    private function limitHomeReviews(Connection $connection): void
    {
        $idsToKeep = $connection->fetchFirstColumn(
            'SELECT avis_id
             FROM avis
             WHERE afficher_accueil = 1
             ORDER BY updated_at DESC, created_at DESC
             LIMIT 2'
        );

        if (count($idsToKeep) < 2) {
            return;
        }

        $connection->executeStatement(
            sprintf(
                'UPDATE avis
                 SET afficher_accueil = 0
                 WHERE afficher_accueil = 1 AND avis_id NOT IN (%s)',
                implode(', ', array_fill(0, count($idsToKeep), '?'))
            ),
            $idsToKeep
        );
    }

    private function ensureScheduleTables(Connection $connection): void
    {
        $connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS horaires_ouverture (
                jour_code VARCHAR(20) NOT NULL PRIMARY KEY,
                jour_label VARCHAR(30) NOT NULL,
                est_ouvert TINYINT(1) NOT NULL DEFAULT 1,
                heure_ouverture TIME NULL,
                heure_fermeture TIME NULL,
                ordre INT NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS fermetures_exceptionnelles (
                fermeture_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                date_fermeture DATE NOT NULL,
                motif VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $defaults = [
            ['lundi', 'Lundi', 1, '09:00:00', '18:00:00', 1],
            ['mardi', 'Mardi', 1, '09:00:00', '18:00:00', 2],
            ['mercredi', 'Mercredi', 1, '09:00:00', '18:00:00', 3],
            ['jeudi', 'Jeudi', 1, '09:00:00', '18:00:00', 4],
            ['vendredi', 'Vendredi', 1, '09:00:00', '18:00:00', 5],
            ['samedi', 'Samedi', 1, '09:00:00', '16:00:00', 6],
            ['dimanche', 'Dimanche', 0, null, null, 7],
        ];

        foreach ($defaults as [$code, $label, $isOpen, $start, $end, $order]) {
            $connection->executeStatement(
                'INSERT IGNORE INTO horaires_ouverture
                    (jour_code, jour_label, est_ouvert, heure_ouverture, heure_fermeture, ordre, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [$code, $label, $isOpen, $start, $end, $order]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getWeeklyHours(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT jour_code, jour_label, est_ouvert,
                    TIME_FORMAT(heure_ouverture, "%H:%i") AS heure_ouverture,
                    TIME_FORMAT(heure_fermeture, "%H:%i") AS heure_fermeture
             FROM horaires_ouverture
             ORDER BY ordre ASC'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getExceptionalClosures(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT fermeture_id, date_fermeture, motif
             FROM fermetures_exceptionnelles
             ORDER BY date_fermeture ASC, fermeture_id ASC
             LIMIT 3'
        );
    }

    private function saveWeeklyHours(Request $request, Connection $connection): void
    {
        $days = $request->request->all('hours');

        foreach ($days as $code => $day) {
            $isOpen = isset($day['est_ouvert']) ? 1 : 0;
            $start = $isOpen ? $this->nullableValue($day['heure_ouverture'] ?? null) : null;
            $end = $isOpen ? $this->nullableValue($day['heure_fermeture'] ?? null) : null;

            $connection->update('horaires_ouverture', [
                'est_ouvert' => $isOpen,
                'heure_ouverture' => $start,
                'heure_fermeture' => $end,
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'jour_code' => $code,
            ]);
        }
    }

    private function addExceptionalClosure(Request $request, Connection $connection): void
    {
        $date = $this->nullableValue($request->request->get('date_fermeture'));
        if ($date === null) {
            return;
        }

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $connection->insert('fermetures_exceptionnelles', [
            'date_fermeture' => $date,
            'motif' => trim((string) $request->request->get('motif', 'Fermeture exceptionnelle')),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAllMenus(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT menu_id AS id, nom_menu AS title, theme, description, personnes_minimum,
                    prix_par_personne, stock_disponible, actif
             FROM menus
             ORDER BY menu_id ASC'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAllMealItems(Connection $connection, string $type): array
    {
        $config = $this->getItemConfig($type);
        if (!$config || $type === 'menu') {
            return [];
        }

        return $connection->fetchAllAssociative(
            sprintf(
                'SELECT item.%s AS id, item.%s AS title, item.theme, item.description, item.allergenes,
                        item.menu_id, item.actif, m.nom_menu, COALESCE(m.actif, 1) AS menu_actif
                 FROM %s item
                 LEFT JOIN menus m ON m.menu_id = item.menu_id
                 ORDER BY item.%s ASC',
                $config['id'],
                $config['name'],
                $config['table'],
                $config['id']
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormViewData(string $type, ?array $item, bool $isCreate, Connection $connection): array
    {
        $labels = [
            'menu' => ['Ajouter un menu', 'Modifier un menu'],
            'entree' => ['Ajouter une entrée', 'Modifier une entrée'],
            'plat' => ['Ajouter un plat', 'Modifier un plat'],
            'dessert' => ['Ajouter un dessert', 'Modifier un dessert'],
        ];

        return [
            'type' => $type,
            'item' => $item ?? [],
            'isCreate' => $isCreate,
            'pageTitle' => $isCreate ? $labels[$type][0] : $labels[$type][1],
            'menus' => $this->getMenuChoices($connection),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getMenuChoices(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu
             FROM menus
             ORDER BY menu_id ASC'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getMenuPayload(Request $request): array
    {
        return [
            'nom_menu' => trim((string) $request->request->get('nom_menu')),
            'theme' => trim((string) $request->request->get('theme')),
            'description' => trim((string) $request->request->get('description')),
            'date_debut_disponibilite' => $this->nullableValue($request->request->get('date_debut_disponibilite')),
            'date_fin_disponibilite' => $this->nullableValue($request->request->get('date_fin_disponibilite')),
            'conditions' => trim((string) $request->request->get('conditions')),
            'personnes_minimum' => max(1, (int) $request->request->get('personnes_minimum')),
            'prix_par_personne' => max(0, (float) str_replace(',', '.', (string) $request->request->get('prix_par_personne'))),
            'stock_disponible' => max(0, (int) $request->request->get('stock_disponible')),
            'actif' => $request->request->getBoolean('actif') ? 1 : 0,
            'image_url' => trim((string) $request->request->get('image_url')),
            'image_alt' => trim((string) $request->request->get('image_alt')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getMealPayload(Request $request): array
    {
        return [
            'menu_id' => $this->nullableValue($request->request->get('menu_id')),
            'theme' => trim((string) $request->request->get('theme')),
            'description' => trim((string) $request->request->get('description')),
            'allergenes' => trim((string) $request->request->get('allergenes')),
            'actif' => $request->request->getBoolean('actif') ? 1 : 0,
            'image_url' => trim((string) $request->request->get('image_url')),
            'image_alt' => trim((string) $request->request->get('image_alt')),
        ] + $this->getMealNamePayload($request);
    }

    /**
     * @return array<string, string>
     */
    private function getMealNamePayload(Request $request): array
    {
        $type = (string) $request->attributes->get('type');

        return match ($type) {
            'entree' => ['nom_entree' => trim((string) $request->request->get('nom'))],
            'plat' => ['nom_plat' => trim((string) $request->request->get('nom'))],
            'dessert' => ['nom_dessert' => trim((string) $request->request->get('nom'))],
            default => [],
        };
    }

    private function nullableValue(mixed $value): mixed
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getPopularMenus(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT m.menu_id AS id, m.nom_menu AS title, m.description, m.prix_par_personne,
                    m.image_url, m.image_alt, m.actif, COUNT(c.commande_id) AS popularity
             FROM menus m
             LEFT JOIN commandes c ON c.menu_id = m.menu_id
             GROUP BY m.menu_id, m.nom_menu, m.description, m.prix_par_personne, m.image_url, m.image_alt, m.actif
             ORDER BY popularity DESC, m.menu_id ASC
             LIMIT 3'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getPopularMealItems(Connection $connection, string $type): array
    {
        $config = $this->getItemConfig($type);
        if (!$config) {
            return [];
        }

        return $connection->fetchAllAssociative(
            sprintf(
                'SELECT item.%s AS id, item.%s AS title, item.description, item.image_url, item.image_alt,
                        item.menu_id, item.actif, m.nom_menu, COALESCE(m.actif, 1) AS menu_actif, COUNT(c.commande_id) AS popularity
                 FROM %s item
                 LEFT JOIN menus m ON m.menu_id = item.menu_id
                 LEFT JOIN commandes c ON c.menu_id = item.menu_id
                 GROUP BY item.%s, item.%s, item.description, item.image_url, item.image_alt, item.menu_id, item.actif, m.nom_menu, m.actif
                 ORDER BY popularity DESC, item.%s ASC
                 LIMIT 3',
                $config['id'],
                $config['name'],
                $config['table'],
                $config['id'],
                $config['name'],
                $config['id']
            )
        );
    }

    /**
     * @return array{table: string, id: string, name: string}|null
     */
    private function getItemConfig(string $type): ?array
    {
        return match ($type) {
            'menu' => ['table' => 'menus', 'id' => 'menu_id', 'name' => 'nom_menu'],
            'entree' => ['table' => 'entree', 'id' => 'entree_id', 'name' => 'nom_entree'],
            'plat' => ['table' => 'plat', 'id' => 'plat_id', 'name' => 'nom_plat'],
            'dessert' => ['table' => 'dessert', 'id' => 'dessert_id', 'name' => 'nom_dessert'],
            default => null,
        };
    }

    private function getReadableItemType(string $type): string
    {
        return match ($type) {
            'menu' => 'un menu',
            'entree' => 'une entree',
            'plat' => 'un plat',
            'dessert' => 'un dessert',
            default => 'un element',
        };
    }

    /**
     * @param array{table: string, id: string, name: string} $config
     */
    private function canActivateMealItem(Connection $connection, array $config, int $id): bool
    {
        $menuStatus = $connection->fetchOne(
            sprintf(
                'SELECT COALESCE(m.actif, 1)
                 FROM %s item
                 LEFT JOIN menus m ON m.menu_id = item.menu_id
                 WHERE item.%s = ?',
                $config['table'],
                $config['id']
            ),
            [$id]
        );

        return (int) $menuStatus === 1;
    }

    private function syncMealItemsWithMenuStatus(Connection $connection, int $menuId, int $status): void
    {
        foreach (['entree', 'plat', 'dessert'] as $table) {
            $connection->update($table, ['actif' => $status], ['menu_id' => $menuId]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveMealItemStatus(Connection $connection, array $payload): int
    {
        if ((int) ($payload['actif'] ?? 0) !== 1 || empty($payload['menu_id'])) {
            return (int) ($payload['actif'] ?? 0);
        }

        $menuStatus = $connection->fetchOne(
            'SELECT actif FROM menus WHERE menu_id = ?',
            [$payload['menu_id']]
        );

        return (int) $menuStatus === 1 ? 1 : 0;
    }
}
