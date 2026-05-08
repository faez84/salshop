<?php

declare(strict_types=1);

namespace App\Basket\Application\UseCase;

use App\Basket\Application\Port\IBasketStore;

class RemoveItemFromBasket
{
    public function __construct(
        private readonly IBasketStore $basketStore
    ) {}

    public function execute(int $productId): void
    {
        $basket = $this->basketStore->getBasket();
        if (!isset($basket)) {
            return;
        }

        if (isset($basket['products'][$productId])) {
            $basket['products'][$productId]--;
            if ($basket['products'][$productId] <= 0) {
                unset($basket['products'][$productId]);
            }
        }

        if ([] === ($basket['products'] ?? [])) {
            $this->basketStore->clearBasket();

            return;
        }

        $this->basketStore->saveBasket($basket);
    }
}
