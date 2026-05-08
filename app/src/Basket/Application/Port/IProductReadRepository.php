<?php

declare(strict_types=1);

namespace App\Basket\Application\Port;

use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
interface IProductReadRepository
{
    public function getProductById(int $productId): ?Product;
}