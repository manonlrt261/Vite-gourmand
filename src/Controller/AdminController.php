<?php

namespace App\Controller;

use App\Service\MongoStatsService;
use App\Validator\InputValidator;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

// Controleur de l'espace administrateur et des statistiques.
class AdminController extends AbstractController
{
    // Affiche le tableau de bord administrateur avec les indicateurs principaux.
    public function dashboard(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->redirectToAdminLogin($request);
        }

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $this->getYearStats($connection),
            'ordersByMenu' => $this->getOrdersByMenu($connection),
            'revenueByMenu' => $this->getRevenueByMenu($connection),
            'latestEmployees' => $this->getLatestEmployees($connection),
        ]);
    }

    // Affiche la page de gestion des employes.
    public function employees(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->redirectToAdminLogin($request);
        }

        $this->ensureEmployeeColumns($connection);
        $this->ensureEmployeeIdentityColumns($connection);

        return $this->render('admin/employees.html.twig', [
            'employees' => $this->getEmployees($connection),
            'jobs' => $this->getEmployeeJobs(),
        ]);
    }

    // Cree un compte employe depuis l espace administrateur.
    public function createEmployee(Request $request, Connection $connection, MailerInterface $mailer): Response
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->redirectToAdminLogin($request);
        }

        $this->ensureEmployeeColumns($connection);
        $this->ensureEmployeeIdentityColumns($connection);

        $formData = $this->getEmployeeFormData($request);
        $errors = [];

        if ($request->isMethod('POST')) {
            // Le token CSRF confirme que la creation vient bien du formulaire administrateur.
            if (!$this->isValidAdminCsrf($request)) {
                $errors[] = 'Le formulaire a expire, veuillez reessayer.';

                return $this->render('admin/employee_form.html.twig', [
                    'jobs' => $this->getEmployeeJobs(),
                    'employee' => $formData,
                    'errors' => $errors,
                ]);
            }

            // Controle les donnees avant de creer un compte employe.
            $errors = $this->validateEmployeeData($connection, $formData, null, true);

            if ($errors === []) {
                $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

                $connection->insert('utilisateurs', [
                    'nom' => $formData['nom'],
                    'prenom' => $formData['prenom'],
                    'date_naissance' => $formData['date_naissance'],
                    'lieu_naissance' => $formData['lieu_naissance'],
                    'adresse_postale' => $formData['adresse_postale'],
                    'code_postal' => $formData['code_postal'],
                    'ville' => $formData['ville'],
                    'email' => $formData['email'],
                    'email_personnel' => $formData['email_personnel'],
                    'telephone' => $formData['telephone'],
                    'poste' => $formData['poste'],
                    'mot_de_passe' => password_hash($formData['password'], PASSWORD_DEFAULT),
                    // Mot de passe initial visible par l'administrateur tant que l'employe ne l'a pas change.
                    'mot_de_passe_initial' => $formData['password'],
                    'role_id' => $this->getEmployeeRoleId($connection),
                    'actif' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $this->sendEmployeeCreationEmail($mailer, $formData);

                $this->addFlash('employee_success', 'Compte employé créé. Un email de confirmation a été envoyé à l’adresse personnelle de l’employé.');

                return $this->redirectToRoute('admin_employees');
            }
        }

        return $this->render('admin/employee_form.html.twig', [
            'jobs' => $this->getEmployeeJobs(),
            'employee' => $formData,
            'errors' => $errors,
        ]);

    }

    // Affiche les statistiques du nombre de commandes par menu.
    public function ordersByMenu(Request $request, Connection $connection, MongoStatsService $mongoStatsService): Response
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->redirectToAdminLogin($request);
        }

        $documents = $mongoStatsService->getOrdersByMenuDocuments($connection);
        $menus = $this->getMenusForOrderStats($connection);
        $themes = $this->getMenuThemes($connection);

        return $this->render('admin/orders_by_menu.html.twig', [
            'documents' => $documents,
            'documentsJson' => json_encode($documents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'menus' => $menus,
            'themes' => $themes,
            'initialStats' => $this->buildOrdersByMenuStats($documents, $menus),
        ]);
    }

    // Affiche les statistiques du chiffre d affaires par menu.
    public function revenueByMenu(Request $request, Connection $connection, MongoStatsService $mongoStatsService): Response
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->redirectToAdminLogin($request);
        }

        $documents = $mongoStatsService->getOrdersByMenuDocuments($connection);
        $menus = $this->getMenusForOrderStats($connection);
        $themes = $this->getMenuThemes($connection);

        return $this->render('admin/revenue_by_menu.html.twig', [
            'documents' => $documents,
            'documentsJson' => json_encode($documents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'menus' => $menus,
            'themes' => $themes,
            'initialStats' => $this->buildRevenueByMenuStats($documents, $menus),
        ]);
    }

    // Active ou desactive un employe sans rechargement de page.
    public function toggleEmployeeStatus(int $id, Request $request, Connection $connection): JsonResponse
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->json(['success' => false], 403);
        }

        // Protection CSRF : l activation/desactivation d un employe est une action sensible.
        if (!$this->isValidAdminCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Formulaire invalide.'], 403);
        }

        $this->ensureEmployeeColumns($connection);
        $this->ensureEmployeeIdentityColumns($connection);
        $employee = $this->getEmployeeById($connection, $id);

        if (!$employee) {
            return $this->json(['success' => false], 404);
        }

        $newStatus = (int) $employee['actif'] === 1 ? 0 : 1;
        $connection->update('utilisateurs', [
            'actif' => $newStatus,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ], [
            'id' => $id,
        ]);

        return $this->json([
            'success' => true,
            'active' => $newStatus === 1,
            'label' => $newStatus === 1 ? 'Actif' : 'Inactif',
        ]);
    }

    // Met a jour les informations d un employe.
    public function updateEmployee(int $id, Request $request, Connection $connection): JsonResponse
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->json(['success' => false], 403);
        }

        // Protection CSRF : la modification d un employe doit venir de la fenetre du site.
        if (!$this->isValidAdminCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Formulaire invalide.'], 403);
        }

        $this->ensureEmployeeColumns($connection);
        $this->ensureEmployeeIdentityColumns($connection);
        $employee = $this->getEmployeeById($connection, $id);

        if (!$employee) {
            return $this->json(['success' => false], 404);
        }

        $payload = [
            'prenom' => trim((string) $request->request->get('prenom')),
            'nom' => trim((string) $request->request->get('nom')),
            'date_naissance' => trim((string) $request->request->get('date_naissance')),
            'lieu_naissance' => trim((string) $request->request->get('lieu_naissance')),
            'email' => trim((string) $request->request->get('email')),
            'email_personnel' => trim((string) $request->request->get('email_personnel')),
            'telephone' => trim((string) $request->request->get('telephone')),
            'adresse_postale' => trim((string) $request->request->get('adresse_postale')),
            'ville' => trim((string) $request->request->get('ville')),
            'code_postal' => trim((string) $request->request->get('code_postal')),
            'poste' => trim((string) $request->request->get('poste')),
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

        // Reutilise les memes controles que la creation pour securiser la modification.
        $errors = $this->validateEmployeeData($connection, $payload, $id);
        if ($errors !== []) {
            return $this->json([
                'success' => false,
                'message' => implode(' ', $errors),
            ], 422);
        }

        if (
            $payload['prenom'] === ''
            || $payload['nom'] === ''
            || $payload['date_naissance'] === ''
            || $payload['lieu_naissance'] === ''
            || $payload['email'] === ''
            || $payload['telephone'] === ''
            || $payload['adresse_postale'] === ''
            || $payload['ville'] === ''
            || $payload['code_postal'] === ''
            || $payload['poste'] === ''
        ) {
            return $this->json([
                'success' => false,
                'message' => 'Tous les champs de l employe sont obligatoires.',
            ], 422);
        }

        if (!$this->isValidDate($payload['date_naissance'])) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez renseigner une date de naissance valide.',
            ], 422);
        }

        if ($payload['prenom'] === '' || $payload['nom'] === '' || $payload['email'] === '' || $payload['poste'] === '') {
            return $this->json([
                'success' => false,
                'message' => 'Les champs prénom, nom, email et poste sont obligatoires.',
            ], 422);
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez renseigner une adresse email valide.',
            ], 422);
        }

        $existingUserId = $connection->fetchOne('SELECT id FROM utilisateurs WHERE email = ? AND id <> ?', [$payload['email'], $id]);
        if ($existingUserId) {
            return $this->json([
                'success' => false,
                'message' => 'Un compte existe dÃ©jÃ  avec cette adresse email.',
            ], 422);
        }

        $connection->update('utilisateurs', $payload, ['id' => $id]);
        $updatedEmployee = $this->getEmployeeById($connection, $id);

        return $this->json([
            'success' => true,
            'employee' => $updatedEmployee,
        ]);
    }

    // Supprime un compte employe apres confirmation administrateur.
    public function deleteEmployee(int $id, Request $request, Connection $connection): JsonResponse
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->json(['success' => false], 403);
        }

        // Protection CSRF : empeche la suppression d un employe par une requete externe.
        if (!$this->isValidAdminCsrf($request)) {
            return $this->json(['success' => false, 'message' => 'Formulaire invalide.'], 403);
        }

        $this->ensureEmployeeColumns($connection);
        $this->ensureEmployeeIdentityColumns($connection);
        $employee = $this->getEmployeeById($connection, $id);

        if (!$employee) {
            return $this->json(['success' => false], 404);
        }

        $connection->delete('utilisateurs', ['id' => $id]);

        return $this->json(['success' => true]);
    }

    // Verifie que l utilisateur connecte est administrateur.
    private function canAccessAdminSpace(Request $request): bool
    {
        $user = $request->getSession()->get('utilisateur');
        $role = is_array($user) ? (string) ($user['role_libelle'] ?? '') : '';
        $roleId = is_array($user) ? (int) ($user['role_id'] ?? 0) : 0;

        return $role === 'administrateur' || $roleId === 3;
    }

    // Verifie le token CSRF commun aux actions sensibles de gestion des employes.
    private function isValidAdminCsrf(Request $request): bool
    {
        return $this->isCsrfTokenValid('admin_employee_action', (string) $request->request->get('_csrf_token'));
    }

    // Redirige vers la connexion si l administrateur n est pas connecte.
    private function redirectToAdminLogin(Request $request): Response
    {
        if (!$request->getSession()->get('utilisateur_id')) {
            return $this->redirectToRoute('login', ['target' => $request->getPathInfo()]);
        }

        return $this->redirectToRoute('employee_dashboard');
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Construit les documents de statistiques depuis les commandes MySQL.
    // Recupere le nombre de commandes par menu depuis MySQL.
    private function getOrdersByMenuDocuments(Connection $connection): array
    {
        $documents = $connection->fetchAllAssociative(
            'SELECT c.commande_id,
                    c.menu_id,
                    COALESCE(m.nom_menu, "Menu supprim&eacute;") AS nom_menu,
                    COALESCE(m.theme, "Non renseign&eacute;") AS theme,
                    c.date_commande,
                    c.nombre_personnes,
                    c.prix_total,
                    COALESCE(sc.code, "") AS statut_code
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             LEFT JOIN statuts_commande sc ON sc.statut_id = c.statut_id
             WHERE COALESCE(sc.code, "") <> ?
             ORDER BY c.date_commande DESC, c.commande_id DESC',
            ['annulee']
        );

        $documents = array_map(static function (array $document): array {
            return [
                'commande_id' => (int) $document['commande_id'],
                'menu_id' => (int) $document['menu_id'],
                'nom_menu' => html_entity_decode((string) $document['nom_menu'], ENT_QUOTES, 'UTF-8'),
                'theme' => self::normalizeMenuTheme(html_entity_decode((string) $document['theme'], ENT_QUOTES, 'UTF-8')),
                'date_commande' => $document['date_commande'] instanceof \DateTimeInterface
                    ? $document['date_commande']->format('Y-m-d')
                    : substr((string) $document['date_commande'], 0, 10),
                'nombre_personnes' => (int) $document['nombre_personnes'],
                'prix_total' => (float) $document['prix_total'],
                'statut_code' => (string) $document['statut_code'],
            ];
        }, $documents);

        $this->writeNoSqlOrdersByMenu($documents);

        return $this->readNoSqlOrdersByMenu();
    }

    /**
     * @param list<array<string, mixed>> $documents
     */
    // Ecrit les statistiques dans la base NoSQL.
    private function writeNoSqlOrdersByMenu(array $documents): void
    {
        $directory = dirname(__DIR__, 2) . '/var/nosql';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $directory . '/commandes_par_menu.json',
            json_encode([
                'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'type' => 'document_store',
                'documents' => $documents,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Lit les statistiques stockees dans la base NoSQL.
    private function readNoSqlOrdersByMenu(): array
    {
        $path = dirname(__DIR__, 2) . '/var/nosql/commandes_par_menu.json';

        if (!is_file($path)) {
            return [];
        }

        $content = json_decode((string) file_get_contents($path), true);

        return is_array($content) && is_array($content['documents'] ?? null) ? $content['documents'] : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Recupere les menus utiles aux pages de statistiques.
    private function getMenusForOrderStats(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, theme, actif
             FROM menus
             ORDER BY nom_menu ASC'
        );
    }

    /**
     * @return list<string>
     */
    // Recupere la liste des themes de menus disponibles.
    private function getMenuThemes(Connection $connection): array
    {
        $themes = array_map(static fn (array $row): string => self::normalizeMenuTheme((string) $row['theme']), $connection->fetchAllAssociative(
            'SELECT DISTINCT theme FROM menus WHERE theme IS NOT NULL AND theme <> "" ORDER BY theme ASC'
        ));

        return array_values(array_unique(array_filter($themes)));
    }

    /**
     * @param list<array<string, mixed>> $documents
     * @param list<array<string, mixed>> $menus
     *
     * @return array<string, mixed>
     */
    // Prepare les donnees du graphique commandes par menu.
    private function buildOrdersByMenuStats(array $documents, array $menus): array
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $currentYearDocuments = array_values(array_filter($documents, static function (array $document) use ($year): bool {
            return (int) substr((string) $document['date_commande'], 0, 4) === $year;
        }));

        $activeMenusCount = count(array_filter($menus, static fn (array $menu): bool => (int) ($menu['actif'] ?? 0) === 1));
        $themesCount = count(array_unique(array_filter(array_map(static fn (array $menu): string => self::normalizeMenuTheme((string) ($menu['theme'] ?? '')), $menus))));

        return [
            'orders_count' => count($currentYearDocuments),
            'active_menus_count' => $activeMenusCount,
            'themes_count' => $themesCount,
            'year' => $year,
        ];
    }

    /**
     * @param list<array<string, mixed>> $documents
     * @param list<array<string, mixed>> $menus
     *
     * @return array<string, mixed>
     */
    // Prepare les donnees du graphique chiffre d affaires par menu.
    private function buildRevenueByMenuStats(array $documents, array $menus): array
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');
        $currentYearDocuments = array_values(array_filter($documents, static function (array $document) use ($year): bool {
            return (int) substr((string) $document['date_commande'], 0, 4) === $year;
        }));

        $activeMenusCount = count(array_filter($menus, static fn (array $menu): bool => (int) ($menu['actif'] ?? 0) === 1));
        $themesCount = count(array_unique(array_filter(array_map(static fn (array $menu): string => self::normalizeMenuTheme((string) ($menu['theme'] ?? '')), $menus))));
        $revenue = array_reduce($currentYearDocuments, static fn (float $sum, array $document): float => $sum + (float) ($document['prix_total'] ?? 0), 0.0);

        return [
            'revenue' => $revenue,
            'active_menus_count' => $activeMenusCount,
            'themes_count' => $themesCount,
            'year' => $year,
        ];
    }

    /**
     * @return array{orders_count: int, revenue: float, customers_count: int, average_rating: float}
     */
    // Calcule les indicateurs annuels du tableau de bord administrateur.
    private function getYearStats(Connection $connection): array
    {
        $year = (int) (new \DateTimeImmutable())->format('Y');

        return [
            'orders_count' => (int) $connection->fetchOne(
                'SELECT COUNT(*) FROM commandes WHERE YEAR(date_commande) = ?',
                [$year]
            ),
            'revenue' => (float) $connection->fetchOne(
                'SELECT COALESCE(SUM(prix_total), 0) FROM commandes WHERE YEAR(date_commande) = ?',
                [$year]
            ),
            'customers_count' => (int) $connection->fetchOne(
                'SELECT COUNT(*)
                 FROM utilisateurs u
                 LEFT JOIN roles r ON r.role_id = u.role_id
                 WHERE YEAR(u.created_at) = ? AND COALESCE(r.libelle, "utilisateur") = ?',
                [$year, 'utilisateur']
            ),
            'average_rating' => (float) $connection->fetchOne('SELECT COALESCE(AVG(note), 0) FROM avis'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Recupere le nombre de commandes par menu depuis MySQL.
    private function getOrdersByMenu(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT COALESCE(m.nom_menu, "Menu supprimÃ©") AS nom_menu,
                    COUNT(c.commande_id) AS total_commandes
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             GROUP BY c.menu_id, m.nom_menu
             ORDER BY total_commandes DESC, nom_menu ASC
             LIMIT 8'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Recupere le chiffre d affaires par menu depuis MySQL.
    private function getRevenueByMenu(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT COALESCE(m.nom_menu, "Menu supprimÃ©") AS nom_menu,
                    COALESCE(SUM(c.prix_total), 0) AS chiffre_affaires
             FROM commandes c
             LEFT JOIN menus m ON m.menu_id = c.menu_id
             GROUP BY c.menu_id, m.nom_menu
             ORDER BY chiffre_affaires DESC, nom_menu ASC
             LIMIT 8'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Recupere les derniers employes ajoutes.
    private function getLatestEmployees(Connection $connection): array
    {
        $this->ensureEmployeeColumns($connection);
        $this->ensureEmployeeIdentityColumns($connection);

        return $connection->fetchAllAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.email_personnel, u.mot_de_passe_initial, u.telephone, u.date_naissance, u.lieu_naissance,
                    u.adresse_postale, u.ville, u.code_postal, u.created_at, u.actif, u.poste
             FROM utilisateurs u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE r.libelle IN (?, ?, ?)
             ORDER BY u.created_at DESC, u.id DESC
             LIMIT 4',
            ['employe', 'employé', 'employÃ©']
        );
    }

    // Ajoute les colonnes employe manquantes si besoin.
    private function ensureEmployeeColumns(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE utilisateurs ADD COLUMN poste VARCHAR(100) NOT NULL DEFAULT "Employé polyvalent"');
        } catch (\Throwable) {
        }

        try {
            // Corrige les anciennes valeurs de poste qui avaient ete enregistrees avec un mauvais encodage.
            $connection->executeStatement(
                'UPDATE utilisateurs SET poste = ? WHERE poste IN (?, ?)',
                ['Employé polyvalent', 'EmployÃ© polyvalent', 'EmployÃƒÂ© polyvalent']
            );
            $connection->executeStatement(
                'UPDATE utilisateurs SET poste = ? WHERE poste IN (?, ?)',
                ['Chargé de clientèle', 'ChargÃ© de clientÃ¨le', 'ChargÃƒÂ© de clientÃƒÂ¨le']
            );
        } catch (\Throwable) {
        }
    }

    // Ajoute les colonnes d identite employe si elles n existent pas.
    private function ensureEmployeeIdentityColumns(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE utilisateurs ADD COLUMN date_naissance DATE DEFAULT NULL');
        } catch (\Throwable) {
        }

        try {
            $connection->executeStatement('ALTER TABLE utilisateurs ADD COLUMN lieu_naissance VARCHAR(150) DEFAULT NULL');
        } catch (\Throwable) {
        }

        try {
            // L'email personnel sert à prévenir l'employé que son compte professionnel a été créé.
            $connection->executeStatement('ALTER TABLE utilisateurs ADD COLUMN email_personnel VARCHAR(255) DEFAULT NULL');
        } catch (\Throwable) {
        }

        try {
            // Ce champ conserve uniquement le mot de passe cree par l'administrateur.
            // Il reste vide si l'employe a deja defini son propre mot de passe.
            $connection->executeStatement('ALTER TABLE utilisateurs ADD COLUMN mot_de_passe_initial VARCHAR(255) DEFAULT NULL');
        } catch (\Throwable) {
        }
    }

    /**
     * @return array<string, string>
     */
    // Recupere les donnees envoyees par les formulaires employe.
    private function getEmployeeFormData(Request $request): array
    {
        return [
            'nom' => trim((string) $request->request->get('nom')),
            'prenom' => trim((string) $request->request->get('prenom')),
            'date_naissance' => trim((string) $request->request->get('date_naissance')),
            'lieu_naissance' => trim((string) $request->request->get('lieu_naissance')),
            'adresse_postale' => trim((string) $request->request->get('adresse_postale')),
            'code_postal' => trim((string) $request->request->get('code_postal')),
            'ville' => trim((string) $request->request->get('ville')),
            'email' => trim((string) $request->request->get('email')),
            'email_personnel' => trim((string) $request->request->get('email_personnel')),
            'telephone' => trim((string) $request->request->get('telephone')),
            'poste' => trim((string) $request->request->get('poste')),
            'password' => (string) $request->request->get('password'),
            'password_confirm' => (string) $request->request->get('password_confirm'),
        ];
    }

    /**
     * @param array<string, string> $data
     * @return list<string>
     */
    // Valide les informations employe avant creation ou modification.
    private function validateEmployeeData(Connection $connection, array $data, ?int $ignoredUserId = null, bool $isCreation = false): array
    {
        $errors = [];

        // Tous les champs d identite employe sont obligatoires.
        foreach (['nom', 'prenom', 'date_naissance', 'lieu_naissance', 'adresse_postale', 'code_postal', 'ville', 'email', 'telephone', 'poste'] as $field) {
            if (($data[$field] ?? '') === '') {
                $errors[] = 'Tous les champs sont obligatoires.';
                break;
            }
        }

        if (($data['email'] ?? '') !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez renseigner une adresse email professionnelle valide.';
        }

        if ($isCreation && ($data['email_personnel'] ?? '') === '') {
            $errors[] = 'Veuillez renseigner une adresse email personnelle.';
        }

        if (($data['email_personnel'] ?? '') !== '' && !filter_var($data['email_personnel'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez renseigner une adresse email personnelle valide.';
        }

        if ($isCreation && (($data['password'] ?? '') === '' || ($data['password_confirm'] ?? '') === '')) {
            $errors[] = 'Veuillez créer et confirmer le mot de passe de l’employé.';
        }

        if ($isCreation && ($data['password'] ?? '') !== ($data['password_confirm'] ?? '')) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }

        if ($isCreation && ($data['password'] ?? '') !== '' && !$this->isStrongPassword($data['password'])) {
            $errors[] = 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
        }

        // La date de naissance doit exister et ne pas etre dans le futur.
        if (($data['date_naissance'] ?? '') !== '' && !$this->isValidDate($data['date_naissance'])) {
            $errors[] = 'Veuillez renseigner une date de naissance valide.';
        }

        if (($data['date_naissance'] ?? '') !== '' && $this->isValidDate($data['date_naissance'])) {
            $birthDate = new \DateTimeImmutable($data['date_naissance']);
            if ($birthDate > new \DateTimeImmutable('today')) {
                $errors[] = 'La date de naissance ne peut pas etre dans le futur.';
            }
        }

        // Controle les formats metier : telephone, code postal et poste autorise.
        if (($data['telephone'] ?? '') !== '' && !InputValidator::isValidPhone($data['telephone'])) {
            $errors[] = 'Veuillez renseigner un numero de telephone valide.';
        }

        if (($data['code_postal'] ?? '') !== '' && !InputValidator::isValidPostalCode($data['code_postal'])) {
            $errors[] = 'Veuillez renseigner un code postal valide a 5 chiffres.';
        }

        if (($data['poste'] ?? '') !== '' && !in_array($data['poste'], $this->getEmployeeJobs(), true)) {
            $errors[] = 'Le poste selectionne est invalide.';
        }

        // Protege la base en limitant chaque champ a la taille prevue.
        $maxLengths = [
            'nom' => 100,
            'prenom' => 100,
            'lieu_naissance' => 150,
            'adresse_postale' => 255,
            'code_postal' => 10,
            'ville' => 250,
            'email' => 255,
            'email_personnel' => 255,
            'telephone' => 20,
            'poste' => 100,
        ];

        foreach ($maxLengths as $field => $maxLength) {
            if (!InputValidator::hasMaxLength($data[$field] ?? '', $maxLength)) {
                $errors[] = 'Certaines informations employe sont trop longues.';
                break;
            }
        }

        // Evite les doublons d email, sauf pour l employe actuellement modifie.
        if (($data['email'] ?? '') !== '') {
            $parameters = [$data['email']];
            $sql = 'SELECT id FROM utilisateurs WHERE email = ?';

            if ($ignoredUserId !== null) {
                $sql .= ' AND id <> ?';
                $parameters[] = $ignoredUserId;
            }

            if ($connection->fetchOne($sql, $parameters)) {
                $errors[] = 'Un compte existe deja avec cette adresse email.';
            }
        }

        return array_values(array_unique($errors));
    }

    // Controle qu une date saisie est valide.
    private function isValidDate(string $date): bool
    {
        $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsedDate instanceof \DateTimeImmutable && $parsedDate->format('Y-m-d') === $date;
    }

    // Vérifie que le mot de passe employé respecte les mêmes règles que les comptes clients.
    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }

    /**
     * @param array<string, string> $employeeData
     */
    // Envoie un email de confirmation sur l'adresse personnelle du nouvel employé.
    private function sendEmployeeCreationEmail(MailerInterface $mailer, array $employeeData): void
    {
        $personalEmail = $employeeData['email_personnel'] ?? '';

        if (!filter_var($personalEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';
        $adminEmail = $_ENV['ADMIN_EMAIL'] ?? $_SERVER['ADMIN_EMAIL'] ?? $from;
        $firstname = trim($employeeData['prenom'] ?? '');
        $lastname = trim($employeeData['nom'] ?? '');
        $professionalEmail = $employeeData['email'] ?? '';
        $fullName = trim($firstname . ' ' . $lastname);

        try {
            $mailer->send((new Email())
                ->from($from)
                ->to($personalEmail)
                ->subject('Votre compte employé Vite & Gourmand a été créé')
                ->text(
                    "Bonjour {$fullName},\n\n"
                    . "Votre compte employé Vite & Gourmand a bien été créé.\n\n"
                    . "Votre email professionnel est : {$professionalEmail}\n\n"
                    . "Pour obtenir votre mot de passe, merci de vous rapprocher de l'administrateur.\n\n"
                    . "À bientôt,\n"
                    . "L'équipe Vite & Gourmand"
                )
                ->html(
                    '<p>Bonjour ' . htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') . ',</p>'
                    . '<p>Votre compte employé <strong>Vite & Gourmand</strong> a bien été créé.</p>'
                    . '<p>Votre email professionnel est : <strong>' . htmlspecialchars($professionalEmail, ENT_QUOTES, 'UTF-8') . '</strong></p>'
                    . '<p>Pour obtenir votre mot de passe, merci de vous rapprocher de l’administrateur.</p>'
                    . '<p>Adresse de contact administrateur : ' . htmlspecialchars($adminEmail, ENT_QUOTES, 'UTF-8') . '</p>'
                    . '<p>À bientôt,<br>L’équipe Vite & Gourmand</p>'
                ));
        } catch (\Throwable) {
            // L'email ne doit pas bloquer la création du compte si le SMTP est indisponible.
        }
    }

    // Recupere l identifiant du role employe.
    private function getEmployeeRoleId(Connection $connection): int
    {
        $roleId = $connection->fetchOne('SELECT role_id FROM roles WHERE libelle IN (?, ?, ?) ORDER BY role_id ASC LIMIT 1', ['employe', 'employé', 'employÃ©']);

        return $roleId ? (int) $roleId : 2;
    }

    // Genere un mot de passe temporaire pour un nouvel employe.
    private function generateTemporaryEmployeePassword(): string
    {
        try {
            return 'Vg!2026' . bin2hex(random_bytes(4));
        } catch (\Throwable) {
            return 'Vg!2026Temp';
        }
    }

    /**
     * @return list<string>
     */
    // Fournit la liste des postes disponibles pour les employes.
    private function getEmployeeJobs(): array
    {
        return [
            'Chef de cuisine',
            'Commis de cuisine',
            'Responsable livraison',
            'Livreur',
            'Chargé de clientèle',
            'Gestionnaire administratif',
            'Employé polyvalent',
        ];
    }

    private static function normalizeMenuTheme(string $theme): string
    {
        return match (mb_strtolower(trim($theme))) {
            'classiques', 'classique' => 'Classique',
            'Ã©vÃ©nementiels', 'evÃ©nementiels', 'Ã©vÃ¨nementiels', 'evÃ¨nementiels', 'evenementiels', 'Ã©vÃ©nements', 'evenements', 'Ã©vÃ©nementiel', 'evÃ©nementiel', 'Ã©vÃ¨nementiel', 'evÃ¨nementiel', 'evenementiel' => 'Ã‰vÃ©nementiel',
            'saisonniers', 'saisonnier' => 'Saisonnier',
            'rÃ©gimes particuliers', 'regimes particuliers', 'rÃ©gime particulier', 'regime particulier', 'rÃ©gimes', 'regimes' => 'RÃ©gime particulier',
            default => trim($theme) !== '' ? trim($theme) : 'Non renseignÃ©',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    // Recupere tous les employes pour la page de gestion.
    private function getEmployees(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.email_personnel, u.mot_de_passe_initial, u.telephone,
                    u.date_naissance, u.lieu_naissance,
                    u.adresse_postale, u.ville, u.code_postal,
                    u.actif, u.created_at, u.updated_at, u.poste
             FROM utilisateurs u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE r.libelle IN (?, ?, ?)
             ORDER BY u.created_at DESC, u.id DESC',
            ['employe', 'employé', 'employÃ©']
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    // Recupere un employe precis par son identifiant.
    private function getEmployeeById(Connection $connection, int $id): array|false
    {
        return $connection->fetchAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.email_personnel, u.mot_de_passe_initial, u.telephone,
                    u.date_naissance, u.lieu_naissance,
                    u.adresse_postale, u.ville, u.code_postal,
                    u.actif, u.created_at, u.updated_at, u.poste
             FROM utilisateurs u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE u.id = ? AND r.libelle IN (?, ?, ?)',
            [$id, 'employe', 'employé', 'employÃ©']
        );
    }
}



