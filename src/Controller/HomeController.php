<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

// Contrôleur de la page d'accueil publique.
class HomeController extends AbstractController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    // Affiche la page d'accueil du site.
    public function show(): Response
    {
        return $this->render('home/home.html.twig', [
            'nextClosure' => $this->findClosureToAnnounce(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findClosureToAnnounce(): ?array
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('Europe/Paris'));

        try {
            $closure = $this->connection->fetchAssociative(
                'SELECT fermeture_id, date_fermeture, date_fin_fermeture, motif
                 FROM fermetures_exceptionnelles
                 WHERE date_fermeture <= :announcement_limit
                   AND COALESCE(date_fin_fermeture, date_fermeture) >= :today
                 ORDER BY date_fermeture ASC, fermeture_id ASC
                 LIMIT 1',
                [
                    'today' => $today->format('Y-m-d'),
                    'announcement_limit' => $today->modify('+14 days')->format('Y-m-d'),
                ]
            );
        } catch (\Throwable) {
            // L'accueil reste disponible si la table n'existe pas encore ou si la base est indisponible.
            return null;
        }

        return $closure ?: null;
    }
}
