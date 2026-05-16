<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Persistence;

use App\Checkout\Infrastructure\Persistence\Doctrine\OrderProduct;

 
interface IOrderProductRepository
{
    public function save(OrderProduct $orderProduct, bool $flush = false): void;
}
