<?php

declare(strict_types=1);

namespace App\Service\Payment;

class PaymentMethodFactory
{
    public function getPaymentMethod(string $paymentMethod): IPayment
    {
        return match ($paymentMethod) {
            'CreditCard' => new CreditcardPayment(),
            'Paypal' => new CreditcardPayment()
        };
    }
}
