<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Payment;

use App\Checkout\Domain\ValueObject\PaymentMethod;
use App\Checkout\Infrastructure\Payment\CreditcardPayment;
use App\Checkout\Infrastructure\Payment\PaypalPayment;
use InvalidArgumentException;

class PaymentGatewayResolver
{
    public function __construct(
        private readonly CreditcardPayment $creditcardPayment,
        private readonly PaypalPayment $paypalPayment
    ) {
    }

    public function getPaymentMethod(string $paymentMethod): PaymentGateway
    {
        $resolvedPaymentMethod = PaymentMethod::fromInput($paymentMethod);
        if (null === $resolvedPaymentMethod) {
            throw new InvalidArgumentException(sprintf('Unsupported payment method "%s".', $paymentMethod));
        }

        return match ($resolvedPaymentMethod) {
            PaymentMethod::CREDIT_CARD => $this->creditcardPayment,
            PaymentMethod::PAYPAL => $this->paypalPayment,
        };
    }
}
