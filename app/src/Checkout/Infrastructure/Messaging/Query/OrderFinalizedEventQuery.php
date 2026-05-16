<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Message\Query;

class OrderFinalizedEventQuery
{
    public function __construct(
        private readonly int $orderId = 0,
        private readonly ?string $status = null

    ) {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}
