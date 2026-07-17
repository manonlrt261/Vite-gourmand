<?php

namespace App\Controller;

use App\Validator\InputValidator;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

// Contrôleur du formulaire de contact public et de sa notification par e-mail.
class ContactController extends AbstractController
{
    // Affiche le formulaire, valide et enregistre le message, puis avertit l'entreprise par e-mail.
    public function index(Request $request, Connection $connection, MailerInterface $mailer): Response
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

        // Envoie une copie du message à l'entreprise, comme demandé dans le sujet.
            $this->sendContactEmailToCompany($mailer, $email, $titre, $description);

            $this->addFlash('contact_success', 'Votre message a bien été envoyé.');

            return $this->redirectToRoute('contact_index');
        }

    // Affiche simplement la page lorsque le formulaire n'a pas encore été envoyé.
        return $this->render('contact/index.html.twig');
    }

    // E-mail : transmet la demande de contact à l'adresse e-mail de l'entreprise.
    private function sendContactEmailToCompany(MailerInterface $mailer, string $visitorEmail, string $title, string $message): void
    {
        $companyEmail = $_ENV['ADMIN_EMAIL'] ?? $_SERVER['ADMIN_EMAIL'] ?? 'contact@vite-gourmand.fr';
        $from = $_ENV['MAILER_FROM'] ?? $_SERVER['MAILER_FROM'] ?? 'contact@vite-gourmand.fr';

        if (!filter_var($companyEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $safeVisitorEmail = htmlspecialchars($visitorEmail, ENT_QUOTES, 'UTF-8');
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

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
        } catch (\Throwable) {
            // Le message reste conservé dans la messagerie même si le SMTP n'est pas encore configuré.
        }
    }
}
