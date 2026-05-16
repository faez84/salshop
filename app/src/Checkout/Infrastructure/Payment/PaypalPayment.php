<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Payment;

use App\Checkout\Application\Port\Payment\PaymentGateway;
use App\Checkout\Domain\ValueObject\PaymentMethod;
use App\Checkout\Domain\ValueObject\PaypalCreateOrderResult;

class PaypalPayment implements PaymentGateway
{
    protected string $paymentName = PaymentMethod::PAYPAL->value;

    public function __construct(
        private readonly PaypalClient $paypalClient
    ) {
    }

    public function executePayment(string $requestId): bool
    {
        // PayPal requires buyer approval + callback capture; it is not a synchronous bool flow.
        return false;
    }

    public function createOrder(
        float $amount,
        string $currencyCode,
        string $returnUrl,
        string $cancelUrl,
        string $requestId
    ): PaypalCreateOrderResult {
        return $this->paypalClient->createOrder($amount, $currencyCode, $returnUrl, $cancelUrl, $requestId);
    }

    public function captureOrder(string $providerOrderId, string $requestId): bool
    {
        return $this->paypalClient->captureOrder($providerOrderId, $requestId);
    }

    public function setPayment(string $paymentName): void
    {
        $this->paymentName = $paymentName;
    }

    public function getPaymentName(): string
    {
        return $this->paymentName;
    }
}
