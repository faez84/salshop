<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Persistence;

use App\Checkout\Infrastructure\Persistence\Doctrine\OrderProduct;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;

 
interface IOrderProductRepository
{
    public function save(OrderProduct $orderProduct, bool $flush = false): void;

    public function updateQuantity(Product $product, int $newQuantity, bool $flush = false): void;
}
