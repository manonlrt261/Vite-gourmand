<?php

namespace App\Service;

use Doctrine\DBAL\Connection;
use MongoDB\Client;

class MongoStatsService
{
    private const DATABASE_NAME = 'vite_gourmand_nosql';
    private const ORDERS_BY_MENU_COLLECTION = 'commandes_par_menu';

    private ?Client $client = null;

    public function getOrdersByMenuDocuments(Connection $connection): array
    {
        $documents = $this->buildOrdersByMenuDocuments($connection);

        try {
            $collection = $this->getClient()
                ->selectDatabase($_ENV['MONGODB_DATABASE'] ?? self::DATABASE_NAME)
                ->selectCollection(self::ORDERS_BY_MENU_COLLECTION);

            $collection->deleteMany([]);

            if ($documents !== []) {
                $collection->insertMany(array_map(static function (array $document): array {
                    return $document + [
                        'synced_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                    ];
                }, $documents));
            }

            return array_map(
                static fn (array $document): array => self::normalizeMongoDocument($document),
                $collection->find([], ['sort' => ['date_commande' => -1, 'commande_id' => -1]])->toArray()
            );
        } catch (\Throwable) {
            return $documents;
        }
    }

    private function getClient(): Client
    {
        if (!$this->client instanceof Client) {
            $this->client = new Client($_ENV['MONGODB_URI'] ?? 'mongodb://127.0.0.1:27017');
        }

        return $this->client;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildOrdersByMenuDocuments(Connection $connection): array
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

        return array_map(static function (array $document): array {
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
    }

    private static function normalizeMongoDocument(array|object $document): array
    {
        $document = (array) $document;

        unset($document['_id'], $document['synced_at']);

        return [
            'commande_id' => (int) ($document['commande_id'] ?? 0),
            'menu_id' => (int) ($document['menu_id'] ?? 0),
            'nom_menu' => (string) ($document['nom_menu'] ?? ''),
            'theme' => self::normalizeMenuTheme((string) ($document['theme'] ?? '')),
            'date_commande' => (string) ($document['date_commande'] ?? ''),
            'nombre_personnes' => (int) ($document['nombre_personnes'] ?? 0),
            'prix_total' => (float) ($document['prix_total'] ?? 0),
            'statut_code' => (string) ($document['statut_code'] ?? ''),
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
}
