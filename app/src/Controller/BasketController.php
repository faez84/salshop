<?php

declare(strict_types=1);

namespace App\Controller;

use App\Basket\Application\UseCase\AddItemToBasket;
use App\Basket\Application\UseCase\GetBasketProducts;
use App\Basket\Application\UseCase\RemoveItemFromBasket;
use App\Catalog\Domain\Service\ProductValidator;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use App\Checkout\Application\Exceptions\OutOfStockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BasketController extends AbstractController
{
    public function __construct(
        protected GetBasketProducts $basketProducts,
        protected AddItemToBasket $basketItems,
        protected RemoveItemFromBasket $removeItemFromBasket,
        protected ProductValidator $productValidator)
    {
    }

    #[Route(path: '/basket/product/{product}', name: "add_basket_product")]
    public function addBasketProduct(Product $product, Request $request): Response
    {
        try {
            $productCount = $this->basketProducts->getProductCount($product->getId());
            $this->productValidator->validate($product, $productCount + 1);
            $this->basketItems->execute($product->getId());
        } catch (OutOfStockException $outOfStockException) {
            $this->addFlash(
                'notice',
                sprintf("product (%s) is " . $outOfStockException->getMessage(), $product->getId())
            );
        }
        $route = $request->headers->get('referer');
        if (!is_string($route) || '' === trim($route)) {
            return $this->redirectToRoute('display_basket');
        }

        return $this->redirect($route);
    }

    public function getBasketProductsCount(): Response
    {
        $count = $this->basketProducts->getBasketProductsCount();

        return $this->render('basket/basket_count.html.twig', [
            'count' => $count,
        ]);
    }

    #[Route('/basket', name: 'display_basket')]
    #[Route('/basket', name: 'disply_basket')]
    public function displayBasket(): Response
    {
        $productsList = $this->basketProducts->getBasketProductsList();
        $basketProducts = $this->basketProducts->getBasketProducts()['products'] ?? [];

        return $this->render('basket/list.html.twig', [
            'products' => $productsList,
            'basketProducts' => $basketProducts
        ]);
    }

    #[Route('/basket/product/{productId}/remove', name: 'delete_basket_product')]
    #[Route('/basketkk/product/{productId}', name: 'delete_basket_product_legacy')]
    public function deleteBasketProduct(int $productId): Response
    {
        $this->removeItemFromBasket->execute($productId);
        $productsList = $this->basketProducts->getBasketProductsList();
        $basketProducts = $this->basketProducts->getBasketProducts()['products'] ?? [];

        return $this->render('basket/list.html.twig', [
            'products' => $productsList,
            'basketProducts' => $basketProducts
        ]);
    }

    #[Route(path: '/basket/product/{productId}/increase', name: "add_basket_product_count")]
    public function addBasketProductCount(int $productId): Response
    {
        $this->basketItems->execute($productId);

        $productsList = $this->basketProducts->getBasketProductsList();
        $basketProducts = $this->basketProducts->getBasketProducts()['products'] ?? [];

        return $this->render('basket/list.html.twig', [
            'products' => $productsList,
            'basketProducts' => $basketProducts
        ]);
    }
}
