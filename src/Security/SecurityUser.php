<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly string $password,
        private readonly string $roleLabel,
        private readonly int $roleId,
        private readonly bool $active,
        private readonly string $firstName = '',
        private readonly string $lastName = '',
        private readonly string $phone = '',
        private readonly string $address = '',
        private readonly string $city = '',
        private readonly string $postalCode = '',
    ) {
    }

    public function getId(): int { return $this->id; }
    public function getUserIdentifier(): string { return $this->email; }
    public function getPassword(): string { return $this->password; }
    public function getRoleLabel(): string { return $this->roleLabel; }
    public function getRoleId(): int { return $this->roleId; }
    public function isActive(): bool { return $this->active; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }

    public function getRoles(): array
    {
        return [match ($this->normalizeRole($this->roleLabel)) {
            'administrateur' => 'ROLE_ADMIN',
            'employe' => 'ROLE_EMPLOYEE',
            default => 'ROLE_USER',
        }];
    }

    /** @return array<string, int|string> */
    public function toLegacySession(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->lastName,
            'prenom' => $this->firstName,
            'email' => $this->email,
            'telephone' => $this->phone,
            'adresse_postale' => $this->address,
            'ville' => $this->city,
            'code_postal' => $this->postalCode,
            'role_id' => $this->roleId,
            'role_libelle' => $this->normalizeRole($this->roleLabel),
            'actif' => $this->active ? 1 : 0,
        ];
    }

    public function eraseCredentials(): void
    {
    }

    private function normalizeRole(string $role): string
    {
        $normalized = mb_strtolower(trim($role));
        $normalized = strtr($normalized, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e']);

        return $normalized;
    }
}
