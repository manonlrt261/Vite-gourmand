<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

// Contrôleur de la page d'accueil publique.
class HomeController extends AbstractController
{
    // Affiche la page d'accueil du site.
    public function show(): Response
    {
        return $this->render('home/home.html.twig');
    }
}
