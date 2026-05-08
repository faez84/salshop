<?php

declare(strict_types=1);

namespace App\Checkout\Application\UseCase\Payment;

use App\Checkout\Application\Port\Payment\PaymentGateway;
use App\Checkout\Domain\ValueObject\PaypalCreateOrderResult;

class CreateExternalPayment
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
    ) {
    }

    public function createOrder(
        float $amount,
        string $currencyCode,
        string $returnUrl,
        string $cancelUrl,
        string $requestId
    ): PaypalCreateOrderResult    {
        return $this->paymentGateway->createOrder($amount, $currencyCode, $returnUrl, $cancelUrl, $requestId);
   }
}