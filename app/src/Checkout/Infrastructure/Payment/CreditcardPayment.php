<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Payment;

use App\Checkout\Domain\ValueObject\PaymentMethod;


class CreditcardPayment
{
    protected string $paymentName = PaymentMethod::CREDIT_CARD->value;

    public function executePayment(): bool
    {
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
