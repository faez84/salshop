<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Payment;

use App\Checkout\Domain\ValueObject\PaypalCreateOrderResult;

interface PaymentGateway
{
    public function executePayment(string $requestId): bool;

    public function createOrder(
        float $amount,
        string $currencyCode,
        string $returnUrl,
        string $cancelUrl,
        string $requestId
    ): PaypalCreateOrderResult;

    public function captureOrder(string $providerOrderId, string $requestId): bool;

    public function getPaymentName(): string;

    public function setPayment(string $paymentName): void;
}
