<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Persistence;


use App\Checkout\Infrastructure\Persistence\Doctrine\Order;

interface IOrderRepository
{
    public function save(Order $order, bool $flush = false): Order;
    public function findOneByIdempotencyKey(string $key): ?Order;
    public function findOneByProviderOrderId(string $providerOrderId): ?Order;
    public function findOrderInLastThreeMonths(): array;
}
