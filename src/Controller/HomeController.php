<?php
// src/Controller/HomeController.php
namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

// Classe qui gère les fonctions la page d'accueil
// AbstractController -> Controller Symfony qui génère des fonctions qui seront souvent utilisées dans le projet
class HomeController extends AbstractController
{
   
    public function show(): Response
    {
        return $this->render('home/home.html.twig');
    }
}
