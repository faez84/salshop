<?php

declare(strict_types=1);

namespace App\Basket\Application\UseCase;

use App\Basket\Application\Port\IBasketStore;
use App\Catalog\Application\Port\Persistence\IProductRepository;

class GetBasketProducts
{
    public function __construct(
                private readonly IBasketStore $basketStore,
                private readonly IProductRepository $productRepository

    ) {}

    public function getProductCount(int $productId): int
    {
        $basket = $this->basketStore->getBasket();
        if (isset($basket['products'][$productId])) {
            return $basket['products'][$productId];
        }

        return 0;
    }

        /**
     * @return array<mixed>
     */
    public function getBasketProducts(): array
    {
        $basket = $this->basketStore->getBasket();

        return $basket ?? [];
    }

    /**
     * @return array<object>
     */
    public function getBasketProductsList(): array
    {
        $products = [];
        $basket = $this->basketStore->getBasket();
        if (isset($basket)) {
            $ids = array_keys($basket['products']);
            if ([] === $ids) {
                return [];
            }

            $products = $this->productRepository->findInValues($ids);
        }

        return $products;
    }

    public function getBasketProductsCount(): float|int
    {
         $basket = $this->basketStore->getBasket();
        if (isset($basket)) {
            return array_sum($basket['products']);
        }

        return 0;
    }
}