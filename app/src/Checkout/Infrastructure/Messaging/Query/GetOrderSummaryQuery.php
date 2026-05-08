<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Query;

final class GetOrderSummaryQuery
{
    public function __construct(private readonly int $orderId = 0)
    {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
