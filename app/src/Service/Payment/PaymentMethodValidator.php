<?php

declare(strict_types=1);

namespace App\Service\Payment;

class PaymentMethodValidator
{
    private const METHODS = ['Paypal', 'CreditCard'];

    public function validate(string $paymentMethod): bool
    {
        return in_array($paymentMethod, self::METHODS, true);
    }

    public function valdiate(string $paymentMethod): bool
    {
        return $this->validate($paymentMethod);
    }
}
