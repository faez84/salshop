<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Persistence;

use App\Catalog\Infrastructure\Persistence\Doctrine\Product;

interface IProductWriteRepository
{
    public function updateQuantity(Product $product, int $newQuantity, bool $flush = false): void;
}
