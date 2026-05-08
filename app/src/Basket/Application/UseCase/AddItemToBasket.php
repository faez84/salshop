<?php

declare(strict_types=1);

namespace App\Basket\Application\UseCase;

use App\Basket\Application\Port\IBasketStore;
use App\Basket\Application\Port\IProductReadRepository;
use App\Basket\Domain\Service\BasketValidator;

class AddItemToBasket
{
    public function __construct(
        private readonly IBasketStore $basketStore,
        private readonly IProductReadRepository $productRepository,
        private readonly BasketValidator $basketValidator
    ) {}

    public function execute(int $productId): void
    {
        $product = $this->productRepository->getProductById($productId);
        if (null === $product) {
            return;
        }

        $basket = $this->getBasket();
        if (!isset($basket)) {
            $this->initBasket($productId);

            return;
        }

        $basket = $this->updateBasket($basket, $productId);

        $this->setBasketToStore($productId, $basket);

    }
    /**
     * @return array<mixed>|null
     */
    public function getBasket(): ?array
    {
        return $this->basketStore->getBasket();
    }

    private function setBasketToStore(int $productId, array $basket): void
    {
        $amount = (int) ($basket['products'][$productId] ?? 0);
        $this->basketValidator->validate($productId, $amount);
        $this->basketStore->saveBasket($basket);
    }

    private function initBasket(int $productId): void
    {
        $basket = [
            'products' => [$productId => 1],
        ];

        $this->setBasketToStore($productId, $basket);
    }

    private function updateBasket(array $basket, int $productId): array
    {
        $basket['products'][$productId] = ($basket['products'][$productId] ?? 0) + 1;

        return $basket;
    }
}
