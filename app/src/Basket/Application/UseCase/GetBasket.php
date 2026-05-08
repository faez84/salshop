<?php

declare(strict_types=1);

namespace App\Basket\Application\UseCase;

use App\Basket\Application\Port\IBasketStore;

class GetBasket
{
    public function __construct(
        private readonly IBasketStore $basketStore
    ) {}

    public function execute(): ?array
    {
        return $this->basketStore->getBasket();
    }
}
