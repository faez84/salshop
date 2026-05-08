<?php

declare(strict_types=1);

namespace App\Checkout\Domain\Event;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class OrderPaymentInitiated
{
    public function __construct(
        private readonly string $orderId,
        private readonly string $paymentMethod,
    ) {
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }
}