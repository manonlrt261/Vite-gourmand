<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

// Contrôleur de la page publique regroupant les informations légales du site.
class LegalController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('legal/index.html.twig');
    }

    // Affiche les conditions contractuelles applicables aux commandes passees sur le site.
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }
}
