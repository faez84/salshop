<?php

declare(strict_types=1);

namespace App\Checkout\Domain\Event;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class OrderCreated
{
    public function __construct(
        private readonly int $orderId,
    ) {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}