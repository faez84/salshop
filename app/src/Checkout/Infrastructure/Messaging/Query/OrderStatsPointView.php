<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Query;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class OrderStatsPointView
{
    public function __construct(
        private readonly int $orderCount,
        private readonly string $dateAsDay,
    ) {
    }

    public function getOrderCount(): int
    {
        return $this->orderCount;
    }

    public function getDateAsDay(): string
    {
        return $this->dateAsDay;
    }
}
