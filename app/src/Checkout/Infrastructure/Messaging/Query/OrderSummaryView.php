<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Query;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class OrderSummaryView
{
    public function __construct(
        private readonly int $orderId = 0,
        private readonly string $status = '',
        private readonly string $paymentMethod = '',
        private readonly float $cost = 0.0,
        private readonly ?string $idempotencyKey = null
    ) {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function getCost(): float
    {
        return $this->cost;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }
}
