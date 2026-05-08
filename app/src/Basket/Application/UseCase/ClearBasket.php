<?php

declare(strict_types=1);

namespace App\Basket\Application\UseCase;

use App\Basket\Application\Port\IBasketStore;

class ClearBasket
{
    public function __construct(
        private readonly IBasketStore $basketStore
    ) {}

    public function clear(): void
    {
        $this->basketStore->clearBasket();
    }
}
