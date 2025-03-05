<?php

declare(strict_types=1);

namespace App\Service\Payment;

class PaymentMethodValidator
{
    private const METHODS = ['Paypal', 'CreditCard'];
    public function valdiate(string $paymentMethod): bool
    {
        if (in_array($paymentMethod, self::METHODS)) {
            return true;
        }

        return false;
    }
}
