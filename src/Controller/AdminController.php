<?php

namespace App\Controller;

use App\Service\MongoStatsService;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractController
{
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

    public function employees(Request $request, Connection $connection): Response
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->redirectToAdminLogin($request);
        }

        $this->ensureEmployeeColumns($connection);

        return $this->render('admin/employees.html.twig', [
            'employees' => $this->getEmployees($connection),
            'jobs' => $this->getEmployeeJobs(),
        ]);
    }

    public function createEmployee(Request $request): Response
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->redirectToAdminLogin($request);
        }

        return $this->render('employee/placeholder.html.twig', [
            'pageTitle' => "Ajout d'un employé",
            'pageDescription' => "Cette page servira à créer un nouveau compte employé.",
        ]);
    }

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

    public function toggleEmployeeStatus(int $id, Request $request, Connection $connection): JsonResponse
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->json(['success' => false], 403);
        }

        $this->ensureEmployeeColumns($connection);
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

    public function updateEmployee(int $id, Request $request, Connection $connection): JsonResponse
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->json(['success' => false], 403);
        }

        $this->ensureEmployeeColumns($connection);
        $employee = $this->getEmployeeById($connection, $id);

        if (!$employee) {
            return $this->json(['success' => false], 404);
        }

        $payload = [
            'prenom' => trim((string) $request->request->get('prenom')),
            'nom' => trim((string) $request->request->get('nom')),
            'email' => trim((string) $request->request->get('email')),
            'telephone' => trim((string) $request->request->get('telephone')),
            'adresse_postale' => trim((string) $request->request->get('adresse_postale')),
            'ville' => trim((string) $request->request->get('ville')),
            'code_postal' => trim((string) $request->request->get('code_postal')),
            'poste' => trim((string) $request->request->get('poste')),
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];

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
                'message' => 'Un compte existe déjà avec cette adresse email.',
            ], 422);
        }

        $connection->update('utilisateurs', $payload, ['id' => $id]);
        $updatedEmployee = $this->getEmployeeById($connection, $id);

        return $this->json([
            'success' => true,
            'employee' => $updatedEmployee,
        ]);
    }

    public function deleteEmployee(int $id, Request $request, Connection $connection): JsonResponse
    {
        if (!$this->canAccessAdminSpace($request)) {
            return $this->json(['success' => false], 403);
        }

        $this->ensureEmployeeColumns($connection);
        $employee = $this->getEmployeeById($connection, $id);

        if (!$employee) {
            return $this->json(['success' => false], 404);
        }

        $connection->delete('utilisateurs', ['id' => $id]);

        return $this->json(['success' => true]);
    }

    private function canAccessAdminSpace(Request $request): bool
    {
        $user = $request->getSession()->get('utilisateur');
        $role = is_array($user) ? (string) ($user['role_libelle'] ?? '') : '';
        $roleId = is_array($user) ? (int) ($user['role_id'] ?? 0) : 0;

        return $role === 'administrateur' || $roleId === 3;
    }

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
    private function getOrdersByMenu(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT COALESCE(m.nom_menu, "Menu supprimé") AS nom_menu,
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
    private function getRevenueByMenu(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT COALESCE(m.nom_menu, "Menu supprimé") AS nom_menu,
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
    private function getLatestEmployees(Connection $connection): array
    {
        $this->ensureEmployeeColumns($connection);

        return $connection->fetchAllAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.created_at, u.actif, u.poste
             FROM utilisateurs u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE r.libelle IN (?, ?)
             ORDER BY u.created_at DESC, u.id DESC
             LIMIT 4',
            ['employe', 'employé']
        );
    }

    private function ensureEmployeeColumns(Connection $connection): void
    {
        try {
            $connection->executeStatement('ALTER TABLE utilisateurs ADD COLUMN poste VARCHAR(100) NOT NULL DEFAULT "Employé polyvalent"');
        } catch (\Throwable) {
        }
    }

    /**
     * @return list<string>
     */
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
            'événementiels', 'evénementiels', 'évènementiels', 'evènementiels', 'evenementiels', 'événements', 'evenements', 'événementiel', 'evénementiel', 'évènementiel', 'evènementiel', 'evenementiel' => 'Événementiel',
            'saisonniers', 'saisonnier' => 'Saisonnier',
            'régimes particuliers', 'regimes particuliers', 'régime particulier', 'regime particulier', 'régimes', 'regimes' => 'Régime particulier',
            default => trim($theme) !== '' ? trim($theme) : 'Non renseigné',
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getEmployees(Connection $connection): array
    {
        return $connection->fetchAllAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone,
                    u.adresse_postale, u.ville, u.code_postal,
                    u.actif, u.created_at, u.updated_at, u.poste
             FROM utilisateurs u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE r.libelle IN (?, ?)
             ORDER BY u.created_at DESC, u.id DESC',
            ['employe', 'employé']
        );
    }

    /**
     * @return array<string, mixed>|false
     */
    private function getEmployeeById(Connection $connection, int $id): array|false
    {
        return $connection->fetchAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone,
                    u.adresse_postale, u.ville, u.code_postal,
                    u.actif, u.created_at, u.updated_at, u.poste
             FROM utilisateurs u
             LEFT JOIN roles r ON r.role_id = u.role_id
             WHERE u.id = ? AND r.libelle IN (?, ?)',
            [$id, 'employe', 'employé']
        );
    }
}
