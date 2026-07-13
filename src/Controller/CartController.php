<?php

namespace App\Controller;

use App\Service\MongoStatsService;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CartController extends AbstractController
{
    private const REQUIRED_CHECKOUT_FIELDS = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse_livraison',
        'code_postal_livraison',
        'ville_livraison',
        'date_prestation',
        'heure_livraison',
    ];

    public function index(Request $request, Connection $connection): Response
    {
        return $this->renderCart($request, $connection);
    }

    public function show(int $id, Request $request, Connection $connection): Response
    {
        if (!$this->addMenuToCart($id, $request, $connection)) {
            throw $this->createNotFoundException('Menu introuvable.');
        }

        return $this->redirectToRoute('cart_index');
    }

    public function add(int $id, Request $request, Connection $connection): Response
    {
        if (!$this->addMenuToCart($id, $request, $connection)) {
            return $this->json([
                'success' => false,
                'message' => 'Ce menu est introuvable ou indisponible.',
            ], 404);
        }

        return $this->json([
            'success' => true,
            'message' => 'Menu ajoute au panier.',
            'cartCount' => count($this->getCartItems($request)),
        ]);
    }

    public function update(Request $request, Connection $connection): Response
    {
        $cartItems = $this->getCartItems($request);
        $quantities = $request->request->all('quantities');

        if ($cartItems === [] || $quantities === []) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => false], 400);
            }

            return $this->redirectToRoute('cart_index');
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, personnes_minimum
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $minimumsById = [];
        foreach ($menus as $menu) {
            $minimumsById[(int) $menu['menu_id']] = (int) $menu['personnes_minimum'];
        }

        foreach ($cartItems as $menuId => $item) {
            $minimum = $minimumsById[$menuId] ?? 1;
            $requestedQuantity = (int) ($quantities[$menuId] ?? $minimum);
            $cartItems[$menuId]['nombre_personnes'] = max($minimum, $requestedQuantity);
        }

        $this->saveCartItems($request, $cartItems);

        if ($request->isXmlHttpRequest()) {
            return $this->jsonCartSummary($request, $connection);
        }

        return $this->redirectToRoute('cart_index');
    }

    public function remove(int $id, Request $request, Connection $connection): Response
    {
        $cartItems = $this->getCartItems($request);
        unset($cartItems[$id]);
        $this->saveCartItems($request, $cartItems);

        if ($request->isXmlHttpRequest()) {
            if ($cartItems === []) {
                return $this->json([
                    'success' => true,
                    'isEmpty' => true,
                ]);
            }

            return $this->jsonCartSummary($request, $connection);
        }

        return $this->redirectToRoute('cart_index');
    }

    public function checkout(Request $request, Connection $connection, MongoStatsService $mongoStatsService): Response
    {
        $session = $request->getSession();
        $isConnected = $this->getUser() !== null || $session->has('utilisateur_id');

        if (!$isConnected) {
            return $this->redirectToRoute('login', ['target' => $this->generateUrl('cart_index')]);
        }

        $checkoutData = [];
        foreach (self::REQUIRED_CHECKOUT_FIELDS as $field) {
            $checkoutData[$field] = trim((string) $request->request->get($field));
        }

        foreach ($checkoutData as $value) {
            if ($value === '') {
                $this->addFlash('cart_error', 'Tous les champs obligatoires doivent être renseignés.');

                return $this->redirectToRoute('cart_index');
            }
        }

        if (!filter_var($checkoutData['email'], FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('cart_error', 'Veuillez renseigner une adresse email valide.');

            return $this->redirectToRoute('cart_index');
        }

        $this->updateCartQuantitiesFromRequest($request, $connection);
        $summary = $this->getCartSummary($request, $connection);

        if (!$summary || $summary['items'] === []) {
            $this->addFlash('cart_error', 'Votre panier est vide.');

            return $this->redirectToRoute('cart_index');
        }

        $userId = (int) $session->get('utilisateur_id');
        $statusId = (int) ($connection->fetchOne(
            'SELECT statut_id FROM statuts_commande WHERE code = ? LIMIT 1',
            ['en_attente']
        ) ?: 1);
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        $orderIds = [];

        $connection->beginTransaction();

        try {
            $commandeColumns = $this->getTableColumns($connection, 'commandes');
            $historiqueColumns = $this->getTableColumns($connection, 'historique_statuts_commande');

            foreach ($summary['items'] as $index => $item) {
                $deliveryPrice = $index === 0 ? $summary['prixLivraison'] : 0;
                $lineTotalAfterDiscount = $item['prixLigne'] - $item['reductionLigne'];
                $commandeData = [
                    'utilisateur_id' => $userId,
                    'menu_id' => (int) $item['menu']['menu_id'],
                    'date_commande' => $today,
                    'date_prestation' => $checkoutData['date_prestation'],
                    'heure_livraison' => $checkoutData['heure_livraison'],
                    'heure_de_livraison' => $checkoutData['heure_livraison'],
                    'adresse_livraison' => $checkoutData['adresse_livraison'],
                    'ville_livraison' => $checkoutData['ville_livraison'],
                    'code_postal_livraison' => $checkoutData['code_postal_livraison'],
                    'nombre_personnes' => $item['nombrePersonnes'],
                    'prix_menu' => $lineTotalAfterDiscount,
                    'prix_livraison' => $deliveryPrice,
                    'prix_total' => $lineTotalAfterDiscount + $deliveryPrice,
                    'pret_materiel' => 0,
                    'motif_annulation' => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                    'statut_id' => $statusId,
                ];

                $connection->insert('commandes', array_intersect_key($commandeData, array_flip($commandeColumns)));
                $orderId = (int) $connection->lastInsertId();
                $orderIds[] = $orderId;

                if ($historiqueColumns !== []) {
                    $historiqueData = [
                        'commande_id' => $orderId,
                        'statut_id' => $statusId,
                        'date_changement' => $now,
                        'commentaire' => 'Commande créée depuis le panier.',
                    ];

                    $connection->insert(
                        'historique_statuts_commande',
                        array_intersect_key($historiqueData, array_flip($historiqueColumns))
                    );
                }
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            $this->addFlash('cart_error', 'Impossible d’enregistrer la commande pour le moment.');

            return $this->redirectToRoute('cart_index');
        }

        $this->saveCartItems($request, []);
        $session->set('last_order_ids', $orderIds);
        $mongoStatsService->getOrdersByMenuDocuments($connection);

        return $this->redirectToRoute('order_confirmation');
    }

    public function confirmation(Request $request): Response
    {
        return $this->render('cart/confirmation.html.twig', [
            'orderIds' => $request->getSession()->get('last_order_ids', []),
        ]);
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
        $normalizedCartItems = [];
        $prixMenu = 0;
        $reduction = 0;

        foreach ($menuIds as $menuId) {
            if (!isset($menusById[$menuId])) {
                continue;
            }

            $menu = $menusById[$menuId];
            $nombrePersonnesMinimum = (int) $menu['personnes_minimum'];
            $nombrePersonnes = max(
                $nombrePersonnesMinimum,
                (int) ($cartItems[$menuId]['nombre_personnes'] ?? $nombrePersonnesMinimum)
            );
            $ligneTotal = (float) $menu['prix_par_personne'] * $nombrePersonnes;
            $ligneReduction = $nombrePersonnes >= $nombrePersonnesMinimum + 5 ? $ligneTotal * 0.10 : 0;
            $prixMenu += $ligneTotal;
            $reduction += $ligneReduction;
            $normalizedCartItems[$menuId] = ['nombre_personnes' => $nombrePersonnes];

            $cartItemsDetailed[] = [
                'menu' => $menu,
                'nombrePersonnes' => $nombrePersonnes,
                'nombrePersonnesMinimum' => $nombrePersonnesMinimum,
                'prixLigne' => $ligneTotal,
                'reductionLigne' => $ligneReduction,
                'prixLigneApresReduction' => $ligneTotal - $ligneReduction,
            ];
        }

        if ($cartItemsDetailed === []) {
            $this->saveCartItems($request, []);

            return $this->render('cart/index.html.twig');
        }

        $this->saveCartItems($request, $normalizedCartItems);

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

    private function jsonCartSummary(Request $request, Connection $connection): JsonResponse
    {
        $cartItems = $this->getCartItems($request);

        if ($cartItems === []) {
            return $this->json([
                'success' => true,
                'isEmpty' => true,
            ]);
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, personnes_minimum, prix_par_personne
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $menusById = [];
        foreach ($menus as $menu) {
            $menusById[(int) $menu['menu_id']] = $menu;
        }

        $items = [];
        $prixMenu = 0;
        $reduction = 0;
        $normalizedCartItems = [];

        foreach ($menuIds as $menuId) {
            if (!isset($menusById[$menuId])) {
                continue;
            }

            $menu = $menusById[$menuId];
            $minimum = (int) $menu['personnes_minimum'];
            $quantity = max($minimum, (int) ($cartItems[$menuId]['nombre_personnes'] ?? $minimum));
            $lineTotal = (float) $menu['prix_par_personne'] * $quantity;
            $lineDiscount = $quantity >= $minimum + 5 ? $lineTotal * 0.10 : 0;
            $prixMenu += $lineTotal;
            $reduction += $lineDiscount;
            $normalizedCartItems[$menuId] = ['nombre_personnes' => $quantity];

            $items[] = [
                'menuId' => $menuId,
                'quantity' => $quantity,
                'minimum' => $minimum,
                'unitPrice' => (float) $menu['prix_par_personne'],
                'lineTotal' => $lineTotal,
                'lineDiscount' => $lineDiscount,
                'lineTotalAfterDiscount' => $lineTotal - $lineDiscount,
            ];
        }

        $this->saveCartItems($request, $normalizedCartItems);

        $prixLivraison = 0;

        return $this->json([
            'success' => true,
            'items' => $items,
            'prixMenu' => $prixMenu,
            'reduction' => $reduction,
            'prixLivraison' => $prixLivraison,
            'prixTotal' => $prixMenu - $reduction + $prixLivraison,
        ]);
    }

    private function updateCartQuantitiesFromRequest(Request $request, Connection $connection): void
    {
        $cartItems = $this->getCartItems($request);
        $quantities = $request->request->all('quantities');

        if ($cartItems === [] || $quantities === []) {
            return;
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, personnes_minimum
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $minimumsById = [];
        foreach ($menus as $menu) {
            $minimumsById[(int) $menu['menu_id']] = (int) $menu['personnes_minimum'];
        }

        foreach ($cartItems as $menuId => $item) {
            $minimum = $minimumsById[$menuId] ?? 1;
            $requestedQuantity = (int) ($quantities[$menuId] ?? $minimum);
            $cartItems[$menuId]['nombre_personnes'] = max($minimum, $requestedQuantity);
        }

        $this->saveCartItems($request, $cartItems);
    }

    private function getCartSummary(Request $request, Connection $connection): ?array
    {
        $cartItems = $this->getCartItems($request);

        if ($cartItems === []) {
            return null;
        }

        $menuIds = array_keys($cartItems);
        $menus = $connection->fetchAllAssociative(
            'SELECT menu_id, nom_menu, personnes_minimum, prix_par_personne
             FROM menus
             WHERE menu_id IN (?) AND actif = 1',
            [$menuIds],
            [ArrayParameterType::INTEGER]
        );

        $menusById = [];
        foreach ($menus as $menu) {
            $menusById[(int) $menu['menu_id']] = $menu;
        }

        $items = [];
        $prixMenu = 0;
        $reduction = 0;

        foreach ($menuIds as $menuId) {
            if (!isset($menusById[$menuId])) {
                continue;
            }

            $menu = $menusById[$menuId];
            $minimum = (int) $menu['personnes_minimum'];
            $quantity = max($minimum, (int) ($cartItems[$menuId]['nombre_personnes'] ?? $minimum));
            $lineTotal = (float) $menu['prix_par_personne'] * $quantity;
            $lineDiscount = $quantity >= $minimum + 5 ? $lineTotal * 0.10 : 0;
            $prixMenu += $lineTotal;
            $reduction += $lineDiscount;

            $items[] = [
                'menu' => $menu,
                'nombrePersonnes' => $quantity,
                'prixLigne' => $lineTotal,
                'reductionLigne' => $lineDiscount,
            ];
        }

        $prixLivraison = 0;

        return [
            'items' => $items,
            'prixMenu' => $prixMenu,
            'reduction' => $reduction,
            'prixLivraison' => $prixLivraison,
            'prixTotal' => $prixMenu - $reduction + $prixLivraison,
        ];
    }

    /**
     * @return list<string>
     */
    private function getTableColumns(Connection $connection, string $tableName): array
    {
        try {
            return array_map(
                static fn (array $column): string => (string) $column['COLUMN_NAME'],
                $connection->fetchAllAssociative(
                    'SELECT COLUMN_NAME
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                    [$tableName]
                )
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{nombre_personnes: int}>
     */
    private function getCartItems(Request $request): array
    {
        $session = $request->getSession();
        $cartItems = $session->get('cart_items', []);
        $legacyMenuId = $session->get('cart_menu_id');

        if ($cartItems === [] && $legacyMenuId) {
            $cartItems[(int) $legacyMenuId] = ['nombre_personnes' => 0];
            $session->remove('cart_menu_id');
        }

        $cleanItems = [];
        foreach ($cartItems as $menuId => $item) {
            if ($item === false || $item === null) {
                continue;
            }

            $cleanItems[(int) $menuId] = [
                'nombre_personnes' => is_array($item) ? (int) ($item['nombre_personnes'] ?? 0) : 0,
            ];
        }

        return $cleanItems;
    }

    /**
     * @param array<int, array{nombre_personnes: int}> $cartItems
     */
    private function saveCartItems(Request $request, array $cartItems): void
    {
        $request->getSession()->set('cart_items', $cartItems);
        $request->getSession()->remove('cart_menu_id');
    }

    private function addMenuToCart(int $id, Request $request, Connection $connection): bool
    {
        $menuExists = (bool) $connection->fetchOne(
            'SELECT 1 FROM menus WHERE menu_id = ? AND actif = 1',
            [$id]
        );

        if (!$menuExists) {
            return false;
        }

        $cartItems = $this->getCartItems($request);
        $cartItems[$id] = $cartItems[$id] ?? ['nombre_personnes' => 0];
        $this->saveCartItems($request, $cartItems);

        return true;
    }

}
