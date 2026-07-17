<?php

namespace App\Controller;

use App\Validator\InputValidator;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

// Contrôleur de l'authentification, de l'inscription et de la réinitialisation du mot de passe.
class AuthController extends AbstractController
{
    // Authentifie un utilisateur actif, initialise sa session et le redirige selon son rôle.
    public function login(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
            // Token CSRF : confirme que la connexion vient bien du formulaire du site.
            if (!$this->isValidAuthCsrf($request)) {
                $this->addFlash('error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->redirectToRoute('login');
            }

            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');

            $user = $connection->fetchAssociative(
                'SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.mot_de_passe, u.adresse_postale, u.ville, u.code_postal, u.role_id, u.actif, r.libelle AS role_libelle
                 FROM utilisateurs u
                 LEFT JOIN roles r ON r.role_id = u.role_id
                 WHERE u.email = ?',
                [$email]
            );

            if (!$user || (int) $user['actif'] !== 1 || !$this->isPasswordValid($password, (string) $user['mot_de_passe'])) {
                $this->addFlash('error', 'Email ou mot de passe incorrect.');

                return $this->redirectToRoute('login');
            }

            $session = $request->getSession();
            $session->set('utilisateur_id', (int) $user['id']);
            $roleLibelle = strtolower((string) ($user['role_libelle'] ?? 'utilisateur'));

            $session->set('utilisateur', [
                'id' => (int) $user['id'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'email' => $user['email'],
                'telephone' => $user['telephone'],
                'adresse_postale' => $user['adresse_postale'],
                'ville' => $user['ville'],
                'code_postal' => $user['code_postal'],
                'role_id' => (int) $user['role_id'],
                'role_libelle' => $roleLibelle,
                'actif' => (int) $user['actif'],
            ]);

            $targetPath = (string) $request->query->get('target');
            if (!str_starts_with($targetPath, '/') || str_starts_with($targetPath, '//')) {
                $targetPath = '';
            }

            return $this->redirect($targetPath ?: $this->generateUrl($this->getDefaultRouteForRole($roleLibelle)));
        }

        return $this->render('auth/login.html.twig');
    }

    // Déconnecte l'utilisateur en vidant sa session.
    public function logout(Request $request): Response
    {
        $request->getSession()->remove('utilisateur_id');
        $request->getSession()->remove('utilisateur');

        return $this->redirectToRoute('home_show');
    }

    // Crée un compte client avec contrôle de l'e-mail et du mot de passe.
    public function register(Request $request, Connection $connection, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            // Jeton CSRF : protège la création de compte contre les envois non voulus.
            if (!$this->isValidAuthCsrf($request)) {
                $this->addFlash('register_error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->render('auth/register.html.twig', ['formData' => []]);
            }

            $data = [
                'prenom' => trim((string) $request->request->get('prenom')),
                'nom' => trim((string) $request->request->get('nom')),
                'email' => trim((string) $request->request->get('email')),
                'password' => (string) $request->request->get('password'),
                'password_confirm' => (string) $request->request->get('password_confirm'),
                'telephone' => trim((string) $request->request->get('telephone')),
                'adresse_postale' => trim((string) $request->request->get('adresse_postale')),
                'code_postal' => trim((string) $request->request->get('code_postal')),
                'ville' => trim((string) $request->request->get('ville')),
            ];

            $requiredFields = ['prenom', 'nom', 'email', 'password', 'password_confirm', 'telephone', 'adresse_postale', 'code_postal', 'ville'];
            foreach ($requiredFields as $field) {
                if ($data[$field] === '') {
                    $this->addFlash('register_error', 'Tous les champs obligatoires doivent être renseignés.');

                    return $this->render('auth/register.html.twig', ['formData' => $data]);
                }
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('register_error', 'Veuillez renseigner une adresse email valide.');

                return $this->render('auth/register.html.twig', ['formData' => $data]);
            }

            // Vérifie le téléphone côté serveur, même si le formulaire HTML a déjà des contraintes.
            if (!InputValidator::isValidPhone($data['telephone'])) {
                $this->addFlash('register_error', 'Veuillez renseigner un numero de telephone valide.');

                return $this->render('auth/register.html.twig', ['formData' => $data]);
            }

            // Vérifie que le code postal est bien au format français attendu.
            if (!InputValidator::isValidPostalCode($data['code_postal'])) {
                $this->addFlash('register_error', 'Veuillez renseigner un code postal valide a 5 chiffres.');

                return $this->render('auth/register.html.twig', ['formData' => $data]);
            }

            // Limite la taille des données envoyées avant insertion dans la table des utilisateurs.
            if (!$this->hasValidRegistrationLengths($data)) {
                $this->addFlash('register_error', 'Certaines informations sont trop longues.');

                return $this->render('auth/register.html.twig', ['formData' => $data]);
            }

            if ($data['password'] !== $data['password_confirm']) {
                $this->addFlash('register_error', 'Les deux mots de passe ne sont pas identiques.');

                return $this->render('auth/register.html.twig', ['formData' => $data]);
            }

            if (!$this->isStrongPassword($data['password'])) {
                $this->addFlash('register_error', 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.');

                return $this->render('auth/register.html.twig', ['formData' => $data]);
            }

            $existingUser = $connection->fetchOne('SELECT id FROM utilisateurs WHERE email = ?', [$data['email']]);
            if ($existingUser) {
                $this->addFlash('register_error', 'Un compte existe déjà avec cette adresse email.');

                return $this->render('auth/register.html.twig', ['formData' => $data]);
            }

            $roleId = $connection->fetchOne('SELECT role_id FROM roles WHERE libelle = ? LIMIT 1', ['utilisateur']) ?: 1;
            $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

            $connection->insert('utilisateurs', [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'mot_de_passe' => password_hash($data['password'], PASSWORD_DEFAULT),
                'adresse_postale' => $data['adresse_postale'],
                'ville' => $data['ville'],
                'code_postal' => $data['code_postal'],
                'role_id' => (int) $roleId,
                'actif' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $userId = (int) $connection->lastInsertId();
            $request->getSession()->set('utilisateur_id', $userId);
            $request->getSession()->set('utilisateur', [
                'id' => $userId,
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'adresse_postale' => $data['adresse_postale'],
                'ville' => $data['ville'],
                'code_postal' => $data['code_postal'],
                'role_id' => (int) $roleId,
                'role_libelle' => 'utilisateur',
                'actif' => 1,
            ]);

            $this->sendWelcomeEmail($mailer, $data);

            $targetPath = (string) $request->query->get('target');
            if (!str_starts_with($targetPath, '/') || str_starts_with($targetPath, '//')) {
                $targetPath = '';
            }

            return $this->redirect($targetPath ?: $this->generateUrl('home_show'));
        }

        return $this->render('auth/register.html.twig', [
            'formData' => [],
        ]);
    }

    // Génère un jeton temporaire et envoie le lien de réinitialisation sans révéler si le compte existe.
    public function forgotPassword(Request $request, Connection $connection, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            // Jeton CSRF : protège la demande de réinitialisation du mot de passe.
            if (!$this->isValidAuthCsrf($request)) {
                $this->addFlash('forgot_error', 'Le formulaire a expiré, veuillez reessayer.');

                return $this->redirectToRoute('forgot_password');
            }

            $email = trim((string) $request->request->get('email'));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('forgot_error', 'Veuillez renseigner une adresse email valide.');

                return $this->redirectToRoute('forgot_password');
            }

            $this->ensurePasswordResetTableExists($connection);

            $user = $connection->fetchAssociative(
                'SELECT id, email, actif FROM utilisateurs WHERE email = ?',
                [$email]
            );

            if ($user && (int) $user['actif'] === 1) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                $expiresAt = (new \DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');

                $connection->insert('password_reset_tokens', [
                    'utilisateur_id' => (int) $user['id'],
                    'token_hash' => $tokenHash,
                    'expires_at' => $expiresAt,
                    'used_at' => null,
                    'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                ]);

                $absoluteResetUrl = $this->generateUrl('reset_password', ['token' => $token], 0);

                $this->sendPasswordResetEmail($mailer, (string) $user['email'], $absoluteResetUrl);
            }

            $this->addFlash('forgot_success', "Si un compte existe avec cette adresse email, un lien de réinitialisation vient d'être envoyé.");

            return $this->redirectToRoute('forgot_password');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

    // Valide le jeton non expiré, remplace le mot de passe puis rend le lien inutilisable.
    public function resetPassword(string $token, Request $request, Connection $connection): Response
    {
        $this->ensurePasswordResetTableExists($connection);
        $tokenHash = hash('sha256', $token);

        $resetRequest = $connection->fetchAssociative(
            'SELECT id, utilisateur_id, expires_at, used_at
             FROM password_reset_tokens
             WHERE token_hash = ?
             ORDER BY created_at DESC
             LIMIT 1',
            [$tokenHash]
        );

        if (!$resetRequest || $resetRequest['used_at'] !== null || new \DateTimeImmutable((string) $resetRequest['expires_at']) < new \DateTimeImmutable()) {
            $this->addFlash('forgot_error', 'Le lien de réinitialisation est invalide ou expiré.');

            return $this->redirectToRoute('forgot_password');
        }

        if ($request->isMethod('POST')) {
            // Jeton CSRF : protège l'enregistrement du nouveau mot de passe.
            if (!$this->isValidAuthCsrf($request)) {
                $this->addFlash('reset_error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->redirectToRoute('reset_password', ['token' => $token]);
            }

            $password = (string) $request->request->get('password');
            $passwordConfirm = (string) $request->request->get('password_confirm');

            if (!$this->isStrongPassword($password)) {
                $this->addFlash('reset_error', 'Le mot de passe doit contenir au minimum 10 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.');

                return $this->redirectToRoute('reset_password', ['token' => $token]);
            }

            if ($password !== $passwordConfirm) {
                $this->addFlash('reset_error', 'Les deux mots de passe ne sont pas identiques.');

                return $this->redirectToRoute('reset_password', ['token' => $token]);
            }

            $connection->update('utilisateurs', [
                'mot_de_passe' => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'id' => (int) $resetRequest['utilisateur_id'],
            ]);

            try {
                // Le lien de réinitialisation définit un nouveau mot de passe personnel.
                // Le mot de passe initial créé par l'administrateur ne doit donc plus être visible.
                $connection->update('utilisateurs', ['mot_de_passe_initial' => null], [
                    'id' => (int) $resetRequest['utilisateur_id'],
                ]);
            } catch (\Throwable) {
            }

            $connection->update('password_reset_tokens', [
                'used_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'id' => (int) $resetRequest['id'],
            ]);

            $this->addFlash('success', 'Votre mot de passe a bien été réinitialisé. Vous pouvez maintenant vous connecter.');

            return $this->redirectToRoute('login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'token' => $token,
        ]);
    }

    // Vérifie le jeton CSRF commun aux formulaires d'authentification.
    private function isValidAuthCsrf(Request $request): bool
    {
        return $this->isCsrfTokenValid('auth_action', (string) $request->request->get('_csrf_token'));
    }

    // Vérifie que le mot de passe saisi correspond au mot de passe stocké.
    private function isPasswordValid(string $password, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        return password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
    }

    // E-mail : envoie un message de bienvenue au client après la création de son compte.
    private function sendWelcomeEmail(MailerInterface $mailer, array $userData): void
    {
        $to = (string) ($userData['email'] ?? '');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $firstName = htmlspecialchars((string) ($userData['prenom'] ?? ''), ENT_QUOTES, 'UTF-8');
        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';

        $html = <<<HTML
            <h1>Bienvenue chez Vite & Gourmand</h1>
            <p>Bonjour {$firstName},</p>
            <p>Votre compte client a bien été créé.</p>
            <p>Vous pouvez désormais vous connecter, consulter nos menus, préparer votre panier et suivre vos commandes depuis votre espace client.</p>
            <p>À très bientôt,<br>L'équipe Vite & Gourmand</p>
        HTML;

        try {
            // Le SMTP configuré dans .env.local est utilisé automatiquement par Symfony Mailer.
            $mailer->send((new Email())
                ->from($from)
                ->to($to)
                ->subject('Bienvenue chez Vite & Gourmand')
                ->html($html));
        } catch (\Throwable) {
            // L'inscription doit rester valide même si l'envoi d'e-mail n'est pas configuré en local.
        }
    }

    // E-mail 2 : envoie au client le lien sécurisé permettant de définir un nouveau mot de passe.
    private function sendPasswordResetEmail(MailerInterface $mailer, string $to, string $resetUrl): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');
        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';

        $html = <<<HTML
            <h1>Réinitialisation de votre mot de passe</h1>
            <p>Bonjour,</p>
            <p>Vous avez demandé la réinitialisation de votre mot de passe Vite & Gourmand.</p>
            <p>Pour définir un nouveau mot de passe, cliquez sur le lien ci-dessous :</p>
            <p><a href="{$safeResetUrl}">Réinitialiser mon mot de passe</a></p>
            <p>Ce lien est valable pendant une heure.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email.</p>
            <p>À très bientôt,<br>L'équipe Vite & Gourmand</p>
        HTML;

        try {
            // Le lien contient un jeton unique et expire après la durée définie lors de la demande.
            $mailer->send((new Email())
                ->from($from)
                ->to($to)
                ->subject('Réinitialisation de votre mot de passe - Vite & Gourmand')
                ->html($html));
        } catch (\Throwable) {
            // La demande reste valide même si le SMTP local n'est pas encore configuré.
        }
    }

    // Détermine la page de redirection selon le rôle de l'utilisateur.
    private function getDefaultRouteForRole(string $roleLibelle): string
    {
        if ($roleLibelle === 'administrateur') {
            return 'admin_dashboard';
        }

        return match ($roleLibelle) {
            'employe', 'employé', 'administrateur' => 'employee_dashboard',
            default => 'customer_account',
        };
    }

    // Contrôle les règles de sécurité du mot de passe.
    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }

    /**
     * @param array<string, string> $data
     */
    // Regroupe les limites de longueur du formulaire d'inscription.
    private function hasValidRegistrationLengths(array $data): bool
    {
        return InputValidator::hasMaxLength($data['prenom'] ?? '', 100)
            && InputValidator::hasMaxLength($data['nom'] ?? '', 100)
            && InputValidator::hasMaxLength($data['email'] ?? '', 255)
            && InputValidator::hasMaxLength($data['telephone'] ?? '', 20)
            && InputValidator::hasMaxLength($data['adresse_postale'] ?? '', 255)
            && InputValidator::hasMaxLength($data['ville'] ?? '', 250)
            && InputValidator::hasMaxLength($data['code_postal'] ?? '', 10);
    }

    // Crée la table de réinitialisation si elle n'existe pas encore.
    private function ensurePasswordResetTableExists(Connection $connection): void
    {
        $connection->executeStatement(
            'CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT AUTO_INCREMENT NOT NULL,
                utilisateur_id INT NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_PASSWORD_RESET_USER (utilisateur_id),
                UNIQUE INDEX UNIQ_PASSWORD_RESET_TOKEN (token_hash),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
        );
    }
}
