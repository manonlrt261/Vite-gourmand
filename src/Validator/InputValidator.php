<?php

namespace App\Validator;

final class InputValidator
{
    // Liste blanche des themes acceptes pour eviter qu une valeur non prevue soit enregistree.
    public const MENU_THEMES = [
        'Classique',
        'Evenementiel',
        'Événementiel',
        'Saisonnier',
        'Regime particulier',
        'Régime particulier',
    ];

    private function __construct()
    {
    }

    // Verifie qu un code postal francais contient exactement 5 chiffres.
    public static function isValidPostalCode(string $postalCode): bool
    {
        return preg_match('/^\d{5}$/', $postalCode) === 1;
    }

    // Verifie le format d un numero de telephone francais, avec ou sans +33.
    public static function isValidPhone(string $phone): bool
    {
        return preg_match('/^(?:\+33|0)[1-9](?:[\s.-]?\d{2}){4}$/', $phone) === 1;
    }

    // Verifie qu une date respecte le format attendu par les formulaires HTML : AAAA-MM-JJ.
    public static function isValidDate(string $date): bool
    {
        $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $date);

        return $parsedDate instanceof \DateTimeImmutable && $parsedDate->format('Y-m-d') === $date;
    }

    // Verifie qu une date est valide et qu elle n est pas deja passee.
    public static function isFutureOrTodayDate(string $date): bool
    {
        if (!self::isValidDate($date)) {
            return false;
        }

        return new \DateTimeImmutable($date) >= new \DateTimeImmutable('today');
    }

    // Verifie qu une heure respecte le format HH:MM ou HH:MM:SS.
    public static function isValidTime(string $time): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time) === 1;
    }

    // Verifie qu une heure est comprise dans le creneau autorise, bornes incluses.
    public static function isTimeBetween(string $time, string $minimumTime, string $maximumTime): bool
    {
        if (!self::isValidTime($time) || !self::isValidTime($minimumTime) || !self::isValidTime($maximumTime)) {
            return false;
        }

        $normalizedTime = substr($time, 0, 5);
        $normalizedMinimumTime = substr($minimumTime, 0, 5);
        $normalizedMaximumTime = substr($maximumTime, 0, 5);

        return $normalizedTime >= $normalizedMinimumTime && $normalizedTime <= $normalizedMaximumTime;
    }

    // Limite la taille des textes pour proteger la base et eviter les saisies trop longues.
    public static function hasMaxLength(?string $value, int $max): bool
    {
        return mb_strlen((string) $value) <= $max;
    }

    // Controle que le theme envoye par un formulaire fait bien partie des themes autorises.
    public static function isAllowedMenuTheme(string $theme): bool
    {
        return in_array($theme, self::MENU_THEMES, true);
    }
}
