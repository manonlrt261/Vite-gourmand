<?php

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class DatabaseUserProvider implements UserProviderInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->connection->fetchAssociative(
            'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.mot_de_passe,
                    u.adresse_postale, u.ville, u.code_postal, u.role_id, u.actif,
                    r.libelle AS role_libelle
             FROM utilisateurs u
             INNER JOIN roles r ON r.role_id = u.role_id
             WHERE LOWER(u.email) = LOWER(?)',
            [trim($identifier)]
        );

        if (!$user || (int) $user['actif'] !== 1) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($identifier);
            throw $exception;
        }

        return new SecurityUser(
            id: (int) $user['id'],
            email: (string) $user['email'],
            password: (string) $user['mot_de_passe'],
            roleLabel: (string) $user['role_libelle'],
            roleId: (int) $user['role_id'],
            active: true,
            firstName: (string) $user['prenom'],
            lastName: (string) $user['nom'],
            phone: (string) $user['telephone'],
            address: (string) $user['adresse_postale'],
            city: (string) $user['ville'],
            postalCode: (string) $user['code_postal'],
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Type utilisateur non supporté : %s.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class;
    }
}
