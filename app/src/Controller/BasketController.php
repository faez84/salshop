<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Exceptions\OutOfStockException;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\Basket\BasketValidator;
use App\Service\BasketManager;
use App\Service\Product\ProductValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class BasketController extends AbstractController
{
    public function __construct(protected BasketManager $basketManager, protected ProductValidator $productValidator)
    {
    }

    #[Route(path: '/basket/product/{product}', name: "add_basket_product")]
    public function addBasketProduct(Product $product, Request $request): Response
    {
        try {
            $productCount = $this->basketManager->getProductCount($product->getId());
            $this->productValidator->validate($product, $productCount + 1);
            $this->basketManager->addToBasket($product->getId(), $product->getPrice());
        } catch (OutOfStockException $outOfStockException) {
            $this->addFlash(
                'notice',
                sprintf("product (%s) is " . $outOfStockException->getMessage(), $product->getId())
            );
        }
        $route = $request->headers->get('referer');

        return $this->redirect($route);
    }

    public function getBasketProductsCount(): Response
    {
        $count = $this->basketManager->getBasketProductsCount();

        return $this->render('basket/basket_count.html.twig', [
            'count' => $count,
        ]);
    }

    #[Route('/basket', name: 'disply_basket')]
    public function dispalyBasket(): Response
    {
        $productsList = $this->basketManager->getBasketProductsList();
        $basketProducts = $this->basketManager->getBasketProducts()['products'];


        //$productCountsList = array_count_values($products["products"]);
        return $this->render('basket/list.html.twig', [
            'products' => $productsList,
            'basketProducts' => $basketProducts
        ]);
    }

    #[Route('/basketkk/product/{productId}', name: 'delete_basket_product')]
    public function deleteBasketProduct(int $productId): Response
    {
        $this->basketManager->deleteFromBasket($productId);
        $productsList = $this->basketManager->getBasketProductsList();
        $basketProducts = $this->basketManager->getBasketProducts()['products'];

        return $this->render('basket/list.html.twig', [
            'products' => $productsList,
            'basketProducts' => $basketProducts
        ]);
    }

    #[Route(path: '/basket/product/{productId}', name: "add_basket_product_count")]
    public function addBasketProductCount(int $productId): Response
    {
        $this->basketManager->addToBasket($productId);

        $productsList = $this->basketManager->getBasketProductsList();
        $basketProducts = $this->basketManager->getBasketProducts()['products'];

        return $this->render('basket/list.html.twig', [
            'products' => $productsList,
            'basketProducts' => $basketProducts
        ]);
    }
}
