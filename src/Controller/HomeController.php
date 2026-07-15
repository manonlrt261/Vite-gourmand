<?php
// src/Controller/HomeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

// Controleur de la page d'accueil publique.
// AbstractController donne acces aux fonctions Symfony utiles, comme render().
class HomeController extends AbstractController
{
    // Affiche la page d'accueil du site.
    // Affiche la page d accueil du site.
    public function show(): Response
    {
        return $this->render('home/home.html.twig');
    }
}
