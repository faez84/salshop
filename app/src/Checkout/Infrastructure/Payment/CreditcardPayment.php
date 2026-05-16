<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Payment;

use App\Checkout\Application\Port\Payment\PaymentGateway;
use App\Checkout\Domain\ValueObject\PaymentMethod;
use App\Checkout\Domain\ValueObject\PaypalCreateOrderResult;


class CreditcardPayment implements PaymentGateway
{
    protected string $paymentName = PaymentMethod::CREDIT_CARD->value;

    public function executePayment(string $requestId): bool
    {
        // Use $requestId as provider idempotency key when integrating a real card gateway.
        return true;
    }

    public function createOrder(
        float $amount,
        string $currencyCode,
        string $returnUrl,
        string $cancelUrl,
        string $requestId
    ): PaypalCreateOrderResult {
        return new PaypalCreateOrderResult(
            providerOrderId: '',
            approvalUrl: '',
        );
    }
    public function captureOrder(string $providerOrderId, string $requestId): bool
    {
        // Credit card payments are captured synchronously in executePayment, so this is a no-op.
        return true;
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
