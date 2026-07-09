<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AuthController extends AbstractController
{
    public function login(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $password = (string) $request->request->get('password');

            $user = $connection->fetchAssociative(
                'SELECT id, nom, prenom, email, telephone, mot_de_passe, adresse_postale, ville, code_postal, role_id, actif
                 FROM utilisateurs
                 WHERE email = ?',
                [$email]
            );

            if (!$user || (int) $user['actif'] !== 1 || !$this->isPasswordValid($password, (string) $user['mot_de_passe'])) {
                $this->addFlash('error', 'Email ou mot de passe incorrect.');

                return $this->redirectToRoute('login');
            }

            $session = $request->getSession();
            $session->set('utilisateur_id', (int) $user['id']);
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
            ]);

            $targetPath = (string) $request->query->get('target');
            if (!str_starts_with($targetPath, '/') || str_starts_with($targetPath, '//')) {
                $targetPath = '';
            }

            return $this->redirect($targetPath ?: $this->generateUrl('home_show'));
        }

        return $this->render('auth/login.html.twig');
    }

    public function logout(Request $request): Response
    {
        $request->getSession()->remove('utilisateur_id');
        $request->getSession()->remove('utilisateur');

        return $this->redirectToRoute('home_show');
    }

    public function register(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
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
            ]);

            $this->addFlash('success', 'Votre compte a bien été créé.');
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

    public function forgotPassword(Request $request, Connection $connection, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
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

                $resetUrl = $this->generateUrl('reset_password', ['token' => $token], 0);
                $absoluteResetUrl = $request->getSchemeAndHttpHost() . $resetUrl;

                $mailer->send((new Email())
                    ->from('noreply@vite-et-gourmand.local')
                    ->to((string) $user['email'])
                    ->subject('Réinitialisation de votre mot de passe')
                    ->text("Bonjour,\n\nPour réinitialiser votre mot de passe, cliquez sur ce lien :\n" . $absoluteResetUrl . "\n\nCe lien est valable 1 heure.\n\nVite & Gourmand")
                    ->html('<p>Bonjour,</p><p>Pour réinitialiser votre mot de passe, cliquez sur ce lien :</p><p><a href="' . htmlspecialchars($absoluteResetUrl, ENT_QUOTES) . '">Réinitialiser mon mot de passe</a></p><p>Ce lien est valable 1 heure.</p><p>Vite & Gourmand</p>'));
            }

            $this->addFlash('forgot_success', 'Si un compte existe avec cette adresse email, un lien de réinitialisation vient d’être envoyé.');

            return $this->redirectToRoute('forgot_password');
        }

        return $this->render('auth/forgot_password.html.twig');
    }

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
            $password = (string) $request->request->get('password');
            $passwordConfirm = (string) $request->request->get('password_confirm');

            if (strlen($password) < 8) {
                $this->addFlash('reset_error', 'Le mot de passe doit contenir au moins 8 caractères.');

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

    private function isPasswordValid(string $password, string $storedPassword): bool
    {
        if ($storedPassword === '') {
            return false;
        }

        return password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[^A-Za-z0-9]/', $password);
    }

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
