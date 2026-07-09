<?php

namespace App\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
    public function index(Request $request, Connection $connection): Response
    {
        return $this->renderCart($request, $connection);
    }

    public function show(int $id, Request $request, Connection $connection): Response
    {
        $cartItems = $this->getCartItems($request);
        $cartItems[$id] = true;
        $this->saveCartItems($request, $cartItems);

        return $this->redirectToRoute('cart_index');
    }

    public function remove(int $id, Request $request): Response
    {
        $cartItems = $this->getCartItems($request);
        unset($cartItems[$id]);
        $this->saveCartItems($request, $cartItems);

        return $this->redirectToRoute('cart_index');
    }

    private function renderCart(Request $request, Connection $connection): Response
    {
        $cartItems = $this->getCartItems($request);

        if ($cartItems === []) {
            return $this->render('cart/index.html.twig');
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, description, personnes_minimum,
                    prix_par_personne, stock_disponible, image_url, image_alt
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        if ($menus === []) {
            $this->saveCartItems($request, []);

            return $this->render('cart/index.html.twig');
        }

        $menusById = [];
        foreach ($menus as $menu) {
            $menusById[(int) $menu['menu_id']] = $menu;
        }

        $cartItemsDetailed = [];
        $prixMenu = 0;
        $reduction = 0;

        foreach ($menuIds as $menuId) {
            if (!isset($menusById[$menuId])) {
                continue;
            }

            $menu = $menusById[$menuId];
            $nombrePersonnes = (int) $menu['personnes_minimum'];
            $ligneTotal = (float) $menu['prix_par_personne'] * $nombrePersonnes;
            $ligneReduction = $nombrePersonnes >= 6 ? $ligneTotal * 0.10 : 0;
            $prixMenu += $ligneTotal;
            $reduction += $ligneReduction;

            $cartItemsDetailed[] = [
                'menu' => $menu,
                'nombrePersonnes' => $nombrePersonnes,
                'prixLigne' => $ligneTotal,
                'reductionLigne' => $ligneReduction,
            ];
        }

        if ($cartItemsDetailed === []) {
            $this->saveCartItems($request, []);

            return $this->render('cart/index.html.twig');
        }

        $prixLivraison = 0;
        $prixTotal = $prixMenu - $reduction + $prixLivraison;
        $session = $request->getSession();
        $customer = $session->get('utilisateur', []);
        $isConnected = $this->getUser() !== null || $session->has('utilisateur_id');

        if ($request->isMethod('POST') && !$isConnected) {
            $this->addFlash('error', 'Vous devez vous connecter ou creer un compte pour valider votre panier.');

            return $this->redirectToRoute('cart_index');
        }

        return $this->render('cart/show.html.twig', [
            'cartItems' => $cartItemsDetailed,
            'prixMenu' => $prixMenu,
            'reduction' => $reduction,
            'prixLivraison' => $prixLivraison,
            'prixTotal' => $prixTotal,
            'isConnected' => $isConnected,
            'customer' => $customer,
        ]);
    }

    /**
     * @return array<int, bool>
     */
    private function getCartItems(Request $request): array
    {
        $session = $request->getSession();
        $cartItems = $session->get('cart_items', []);
        $legacyMenuId = $session->get('cart_menu_id');

        if ($cartItems === [] && $legacyMenuId) {
            $cartItems[(int) $legacyMenuId] = true;
            $session->remove('cart_menu_id');
        }

        $cleanItems = [];
        foreach ($cartItems as $menuId => $enabled) {
            if ($enabled) {
                $cleanItems[(int) $menuId] = true;
            }
        }

        return $cleanItems;
    }

    /**
     * @param array<int, bool> $cartItems
     */
    private function saveCartItems(Request $request, array $cartItems): void
    {
        $request->getSession()->set('cart_items', $cartItems);
        $request->getSession()->remove('cart_menu_id');
    }
}
