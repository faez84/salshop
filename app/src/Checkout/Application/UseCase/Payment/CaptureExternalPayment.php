<?php
declare(strict_types=1);

namespace App\Checkout\Application\UseCase\Payment;

use App\Checkout\Application\Port\Payment\PaymentGateway;

class CaptureExternalPayment
{
    public function __construct(
        private readonly PaymentGateway $paymentGateway,
    ) {
    }

    public function captureOrder(string $providerOrderId, string $requestId): bool
    {
        return $this->paymentGateway->capture($providerOrderId, $requestId);
    }
}