<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

// Contrôleur du catalogue public et du détail des menus actifs.
class MenuController extends AbstractController
{
    // Récupère les menus actifs, calcule leur popularité et les regroupe par thème pour l'affichage.
    public function index(Connection $connection): Response
    {
        $menus = $connection->fetchAllAssociative(
            'SELECT m.menu_id, m.nom_menu, m.theme, m.description, m.personnes_minimum,
                    m.prix_par_personne, m.stock_disponible, m.image_url, m.image_alt,
                    COUNT(c.commande_id) AS popularite
             FROM menus m
             LEFT JOIN commandes c ON c.menu_id = m.menu_id
             WHERE m.actif = 1
             GROUP BY m.menu_id, m.nom_menu, m.theme, m.description, m.personnes_minimum,
                      m.prix_par_personne, m.stock_disponible, m.image_url, m.image_alt
             ORDER BY m.menu_id ASC'
        );

        $groupedMenus = [
            'Menus intemporels' => [],
            'Menus evenementiels' => [],
            'Menus saisonniers' => [],
            'Regime particulier' => [],
        ];

        foreach ($menus as $menu) {
            $menu['theme'] = $this->normalizeTheme((string) $menu['theme']);

            // Associe les variantes de thèmes enregistrées en base aux sections attendues par la vue.
            $section = match ($menu['theme']) {
                'Classique' => 'Menus intemporels',
                'Evenementiel' => 'Menus evenementiels',
                'Regime particulier' => 'Regime particulier',
                'Saisonnier' => 'Menus saisonniers',
                default => $menu['theme'],
            };

            $groupedMenus[$section][] = $menu;
        }

        return $this->render('menu/index.html.twig', [
            'menus' => $menus,
            'groupedMenus' => $groupedMenus,
        ]);
    }

    // Affiche un menu actif et le premier élément trouvé pour chaque étape du repas.
    public function show(int $id, Connection $connection): Response
    {
        $menu = $connection->fetchAssociative(
            'SELECT menu_id, nom_menu, theme, description, conditions, personnes_minimum,
                    prix_par_personne, stock_disponible, image_url, image_alt
             FROM menus
             WHERE menu_id = ? AND actif = 1',
            [$id]
        );

        if (!$menu) {
            throw $this->createNotFoundException('Menu introuvable.');
        }

        $menu['theme'] = $this->normalizeTheme((string) $menu['theme']);

        $entree = $connection->fetchAssociative(
            'SELECT nom_entree AS nom, description, allergenes, image_url, image_alt
             FROM entree
             WHERE menu_id = ?
             LIMIT 1',
            [$id]
        );

        $plat = $connection->fetchAssociative(
            'SELECT nom_plat AS nom, description, allergenes, image_url, image_alt
             FROM plat
             WHERE menu_id = ?
             LIMIT 1',
            [$id]
        );

        $dessert = $connection->fetchAssociative(
            'SELECT nom_dessert AS nom, description, allergenes, image_url, image_alt
             FROM dessert
             WHERE menu_id = ?
             LIMIT 1',
            [$id]
        );

        return $this->render('menu/show.html.twig', [
            'menu' => $menu,
            'mealItems' => [
                'Entree' => $entree,
                'Plat' => $plat,
                'Dessert' => $dessert,
            ],
        ]);
    }

    // Normalise les variantes historiques d'un thème pour garantir un classement cohérent.
    private function normalizeTheme(string $theme): string
    {
        return match ($theme) {
            'Classiques', 'Classique' => 'Classique',
            'Événementiels', 'Evénementiels', 'Evenementiels', 'Evènementiels', 'Événementiel', 'Evenementiel', 'Evénementiel', 'Evènementiel' => 'Evenementiel',
            'Saisonniers', 'Saisonnier' => 'Saisonnier',
            'Régimes particuliers', 'Regimes particuliers', 'Régime particulier', 'Regime particulier' => 'Regime particulier',
            default => $theme,
        };
    }
}
