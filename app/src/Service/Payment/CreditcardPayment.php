<?php

declare(strict_types=1);

namespace App\Service\Payment;

class CreditcardPayment implements IPayment
{
    protected string $paymentName = 'Creditcard';

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
