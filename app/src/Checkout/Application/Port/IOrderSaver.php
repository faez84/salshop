<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port;

use App\Checkout\Infrastructure\Persistence\Doctrine\Order;

interface IOrderSaver
{
    public function save(
        string $payment,
        string $addressId,
        float $cost,
        string $idempotencyKey,
        ?string $promotionCode = null,
        float $discountAmount = 0.0
    ): Order;
}
