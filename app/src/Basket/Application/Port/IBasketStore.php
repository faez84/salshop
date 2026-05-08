<?php

declare(strict_types=1);

namespace App\Basket\Application\Port;

interface IBasketStore
{
    public function getBasket(): ?array;
    public function saveBasket(array $basket): void;
    public function clearBasket(): void;
}
