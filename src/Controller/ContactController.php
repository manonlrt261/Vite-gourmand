<?php

namespace App\Controller;

use App\Validator\InputValidator;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

// Contrôleur du formulaire de contact public et de sa notification par e-mail.
class ContactController extends AbstractController
{
    // Affiche le formulaire, valide et enregistre le message, puis avertit l'entreprise par e-mail.
    public function index(Request $request, Connection $connection, MailerInterface $mailer, LoggerInterface $logger): Response
    {
        // Le traitement n'est exécuté qu'à la soumission du formulaire.
        if ($request->isMethod('POST')) {
            // Token CSRF : confirme que le message vient bien du formulaire de contact du site.
            if (!$this->isCsrfTokenValid('contact_action', (string) $request->request->get('_csrf_token'))) {
                $this->addFlash('contact_error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->redirectToRoute('contact_index');
            }

            $email = trim((string) $request->request->get('email'));
            $titre = trim((string) $request->request->get('titre'));
            $description = trim((string) $request->request->get('description'));

        // Vérification que tous les champs obligatoires sont remplis.
            if ($email === '' || $titre === '' || $description === '') {
                $this->addFlash('contact_error', 'Tous les champs obligatoires doivent etre remplis.');

                return $this->redirectToRoute('contact_index');
            }

        // Vérification du format de l'adresse e-mail.
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('contact_error', 'Veuillez renseigner une adresse email valide.');

                return $this->redirectToRoute('contact_index');
            }

        // Bloque les messages trop longs avant l'enregistrement et l'envoi de l'e-mail.
            if (
                !InputValidator::hasMaxLength($email, 255)
                || !InputValidator::hasMaxLength($titre, 150)
                || !InputValidator::hasMaxLength($description, 5000)
            ) {
                $this->addFlash('contact_error', 'Votre demande contient un champ trop long.');

                return $this->redirectToRoute('contact_index');
            }

        // Enregistre le message dans la table contact pour la messagerie interne.
            $connection->insert('contact', [
                'email' => $email,
                'titre' => $titre,
                'description' => $description,
                'statut' => 'nouveau',
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'utilisateur_id' => null,
            ]);

        // Avertit l'entreprise et confirme au visiteur que sa demande a bien été reçue.
            $emailsSent = $this->sendContactEmails($mailer, $logger, $email, $titre, $description);

            if ($emailsSent) {
                $this->addFlash('contact_success', 'Votre message a bien été envoyé. Un email de confirmation vous a été adressé.');
            } else {
                $this->addFlash('contact_error', 'Votre message a bien été enregistré, mais l’envoi de la confirmation a échoué.');
            }

            return $this->redirectToRoute('contact_index');
        }

    // Affiche simplement la page lorsque le formulaire n'a pas encore été envoyé.
        return $this->render('contact/index.html.twig');
    }

    // E-mails : avertit l'entreprise puis confirme la réception au visiteur.
    private function sendContactEmails(
        MailerInterface $mailer,
        LoggerInterface $logger,
        string $visitorEmail,
        string $title,
        string $message
    ): bool
    {
        $companyEmail = $_ENV['ADMIN_EMAIL'] ?? $_SERVER['ADMIN_EMAIL'] ?? 'contact@vite-gourmand.fr';
        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';

        if (!filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
            $logger->error('Envoi du formulaire de contact impossible : ADMIN_EMAIL est invalide.');

            return false;
        }

        $safeVisitorEmail = htmlspecialchars($visitorEmail, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        $companyNotificationSent = false;
        $visitorConfirmationSent = false;

        try {
            // Le visiteur est ajouté en replyTo pour que l'entreprise puisse répondre directement.
            $mailer->send((new Email())
                ->from($from)
                ->to($companyEmail)
                ->replyTo($visitorEmail)
                ->subject('Nouvelle demande de contact - Vite & Gourmand')
                ->html(
                    '<h1>Nouvelle demande de contact</h1>' .
                    '<p><strong>Adresse email du visiteur :</strong> ' . $safeVisitorEmail . '</p>' .
                    '<p><strong>Sujet :</strong> ' . $safeTitle . '</p>' .
                    '<h2>Message</h2>' .
                    '<p>' . $safeMessage . '</p>' .
                    '<p>Ce message a également été enregistré dans la messagerie interne.</p>'
                ));
            $companyNotificationSent = true;
        } catch (\Throwable $exception) {
            $logger->error('Échec de la notification du formulaire de contact à l’entreprise.', [
                'exception' => $exception,
            ]);
        }

        try {
            $mailer->send((new Email())
                ->from($from)
                ->to($visitorEmail)
                ->replyTo($companyEmail)
                ->subject('Nous avons bien reçu votre demande - Vite & Gourmand')
                ->html(
                    '<h1>Votre demande a bien été reçue</h1>' .
                    '<p>Bonjour,</p>' .
                    '<p>Merci d’avoir contacté Vite & Gourmand. Notre équipe reviendra vers vous dans les meilleurs délais.</p>' .
                    '<p><strong>Sujet :</strong> ' . $safeTitle . '</p>' .
                    '<h2>Votre message</h2>' .
                    '<p>' . $safeMessage . '</p>' .
                    '<p>À bientôt,<br>L’équipe Vite & Gourmand</p>'
                ));
            $visitorConfirmationSent = true;
        } catch (\Throwable $exception) {
            $logger->error('Échec de l’email de confirmation du formulaire de contact au visiteur.', [
                'exception' => $exception,
            ]);
        }

        return $companyNotificationSent && $visitorConfirmationSent;
    }
}
