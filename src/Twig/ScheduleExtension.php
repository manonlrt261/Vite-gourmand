<?php

namespace App\Twig;

use Doctrine\DBAL\Connection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ScheduleExtension extends AbstractExtension
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('business_hours_lines', [$this, 'getBusinessHoursLines']),
        ];
    }

    /**
     * @return list<string>
     */
    public function getBusinessHoursLines(): array
    {
        try {
            $hours = $this->connection->fetchAllAssociative(
                'SELECT jour_label, est_ouvert,
                        TIME_FORMAT(heure_ouverture, "%Hh%i") AS heure_ouverture,
                        TIME_FORMAT(heure_fermeture, "%Hh%i") AS heure_fermeture
                 FROM horaires_ouverture
                 WHERE est_ouvert = 1
                 ORDER BY ordre ASC'
            );
        } catch (\Throwable) {
            return [
                'Du lundi au vendredi : de 09h à 18h',
                'Le samedi : de 09h à 16h',
            ];
        }

        if ($hours === []) {
            return ['Fermé temporairement'];
        }

        $lines = [];
        $currentGroup = [];
        $currentStart = null;
        $currentEnd = null;

        foreach ($hours as $day) {
            $start = (string) $day['heure_ouverture'];
            $end = (string) $day['heure_fermeture'];

            if ($currentGroup !== [] && ($start !== $currentStart || $end !== $currentEnd)) {
                $lines[] = $this->formatHoursLine($currentGroup, $currentStart, $currentEnd);
                $currentGroup = [];
            }

            $currentGroup[] = strtolower((string) $day['jour_label']);
            $currentStart = $start;
            $currentEnd = $end;
        }

        if ($currentGroup !== []) {
            $lines[] = $this->formatHoursLine($currentGroup, $currentStart, $currentEnd);
        }

        return $lines;
    }

    /**
     * @param list<string> $days
     */
    private function formatHoursLine(array $days, ?string $start, ?string $end): string
    {
        $hours = sprintf('de %s à %s', $start ?: '--h--', $end ?: '--h--');

        if (count($days) === 1) {
            return sprintf('Le %s : %s', $days[0], $hours);
        }

        return sprintf('Du %s au %s : %s', $days[0], $days[array_key_last($days)], $hours);
    }
}
