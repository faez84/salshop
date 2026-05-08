<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port;

use App\Checkout\Infrastructure\Persistence\Doctrine\Order;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;

interface IOrderArticleSaver
{
    /**
     * @param array<int|string, int|string> $productIds
     * @param array<int, Product>|null $productsById
     */
    public function save(Order $order, array $productIds, ?array $productsById = null): void;
}
