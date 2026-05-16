<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Query;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
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
