<?php

namespace App\Controller;

use App\Validator\InputValidator;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

// Controleur du formulaire de contact public.
class ContactController extends AbstractController
{
    // Affiche le formulaire de contact, enregistre le message et envoie une copie a l'entreprise.
    public function index(Request $request, Connection $connection, MailerInterface $mailer): Response
    {
        // Si le formulaire est envoye, on recupere et nettoie les champs.
        if ($request->isMethod('POST')) {
            // Token CSRF : confirme que le message vient bien du formulaire de contact du site.
            if (!$this->isCsrfTokenValid('contact_action', (string) $request->request->get('_csrf_token'))) {
                $this->addFlash('contact_error', 'Le formulaire a expire, veuillez reessayer.');

                return $this->redirectToRoute('contact_index');
            }

            $email = trim((string) $request->request->get('email'));
            $titre = trim((string) $request->request->get('titre'));
            $description = trim((string) $request->request->get('description'));

            // Verification que tous les champs obligatoires sont remplis.
            if ($email === '' || $titre === '' || $description === '') {
                $this->addFlash('contact_error', 'Tous les champs obligatoires doivent etre remplis.');

                return $this->redirectToRoute('contact_index');
            }

            // Verification du format de l'adresse email.
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('contact_error', 'Veuillez renseigner une adresse email valide.');

                return $this->redirectToRoute('contact_index');
            }

            // Bloque les messages trop longs avant l enregistrement et l envoi email.
            if (
                !InputValidator::hasMaxLength($email, 255)
                || !InputValidator::hasMaxLength($titre, 150)
                || !InputValidator::hasMaxLength($description, 5000)
            ) {
                $this->addFlash('contact_error', 'Votre demande contient un champ trop long.');

                return $this->redirectToRoute('contact_index');
            }

            // Enregistrement du message dans la table contact pour la messagerie interne.
            $connection->insert('contact', [
                'email' => $email,
                'titre' => $titre,
                'description' => $description,
                'statut' => 'nouveau',
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'utilisateur_id' => null,
            ]);

            // Envoi d'une copie du message a l'entreprise, comme demande dans le sujet.
            $this->sendContactEmailToCompany($mailer, $email, $titre, $description);

            $this->addFlash('contact_success', 'Votre message a bien été envoyé.');

            return $this->redirectToRoute('contact_index');
        }

        // Affichage simple de la page quand le formulaire n'est pas encore envoye.
        return $this->render('contact/index.html.twig');
    }

    // Email : transmet la demande de contact a l'adresse email de l'entreprise.
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
            // Le visiteur est ajoute en replyTo pour que l'entreprise puisse repondre directement.
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
            // Le message reste conserve dans la messagerie meme si le SMTP n'est pas encore configure.
        }
    }
}
