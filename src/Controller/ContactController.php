<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends AbstractController
{
    public function index(Request $request, Connection $connection): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email'));
            $titre = trim((string) $request->request->get('titre'));
            $description = trim((string) $request->request->get('description'));

            if ($email === '' || $titre === '' || $description === '') {
                $this->addFlash('contact_error', 'Tous les champs obligatoires doivent être remplis.');

                return $this->redirectToRoute('contact_index');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('contact_error', 'Veuillez renseigner une adresse email valide.');

                return $this->redirectToRoute('contact_index');
            }

            $connection->insert('contact', [
                'email' => $email,
                'titre' => $titre,
                'description' => $description,
                'statut' => 'nouveau',
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
                'updated_at' => null,
                'utilisateur_id' => null,
            ]);

            $this->addFlash('contact_success', 'Votre message a bien été envoyé.');

            return $this->redirectToRoute('contact_index');
        }

        return $this->render('contact/index.html.twig');
    }
}
