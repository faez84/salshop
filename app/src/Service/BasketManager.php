<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\ProductRepository;
use App\Service\Basket\BasketValidator;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BasketManager
{
    protected SessionInterface $session;

    public function __construct(
        protected RequestStack $requestStack,
        protected ProductRepository $productRepository,
        protected BasketValidator $basketValidator
    ) {
        $this->session = $requestStack->getSession();
    }

    public function getBasket(): ?array
    {
        return $this->session->get('basket');
    }

    private function initBasket(int $productId, float $price): void
    {
        $basket = [
            "products" => [$productId => [
                "amount" => 1,
                "price" => $price
            ]
            ],
            "cost" => $price
        ];

        $this->setBasketToSession($productId, $price, $basket);
    }

    public function updateBasket(array $basket, int $productId, float $price): array
    {
        if (!isset($basket['products'][$productId])) {
            $basket['products'][$productId] = [
                "amount" => 1,
                "price" => $price
            ];

            return $basket;
        }

        $basket['products'][$productId]['amount']++;

        return $basket;
    }

    /**
     * @param int $productId
     * @param float $price
     * @param array $basket
     * @return void
     * @throws \App\Exceptions\OutOfStockException
     */
    private function setBasketToSession(int $productId, float $price, array $basket): void
    {
        $basket['cost'] += $price;
        $amount = $basket['products'][$productId]['amount'] ?? 0;
        $this->basketValidator->validate($productId, $amount);
        $this->session->set('basket', $basket);
    }

    public function addToBasket(int $productId, float $price = 0.0): void
    {

        $basket = $this->getBasket();
        if (!isset($basket)) {
            $this->initBasket($productId, $price);

            return;
        }

        $basket = $this->updateBasket($basket, $productId, $price);

        $this->setBasketToSession($productId, $price, $basket);
    }

    public function deleteFromBasket(int $productId): void
    {
        $basket = $this->session->get('basket');
        if (!isset($basket)) {
            return;
        }

        $basket = $this->session->get('basket');
        if (isset($basket['products'][$productId])) {
            $basket['products'][$productId]['amount']--;
            if ($basket['products'][$productId]['amount'] == 0) {
                unset($basket['products'][$productId]);
            }
        }

        $this->session->set('basket', $basket);
    }

    public function setRequestStack(RequestStack $requestStack): void
    {
        $this->requestStack = $requestStack;
        $this->session = $requestStack->getSession();
    }

    public function getBasketProductsCount(): float|int
    {
        $basket = $this->session->get('basket');
        if (isset($basket)) {
            return array_sum(array_column($basket["products"], 'amount'));
        }

        return 0;
    }

    public function getBasketProducts()
    {
        $basket = $this->session->get('basket');

        return $basket ?? [];
    }

    public function getBasketProductsList(): array
    {
        $products = [];
        $basket = $this->session->get('basket');
        if (isset($basket)) {
            $ids = array_keys($basket["products"]);
            $products = $this->productRepository->findInValues($ids);
        }

        return $products;
    }

    public function resetBasket(): void
    {
        $this->session->remove('basket');
    }

    public function getProductCount(int $productId): int
    {
        $basket = $this->session->get('basket');
        if (isset($basket['products'][$productId])) {
            return $basket['products'][$productId]['amount'];
        }

        return 0;
    }
}
