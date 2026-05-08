<?php

declare(strict_types=1);

namespace App\Checkout\Domain\Service;

use App\Checkout\Domain\ValueObject\PaymentMethod;

class PaymentMethodValidator
{
    public function validate(string $paymentMethod): bool
    {
        return null !== PaymentMethod::fromInput($paymentMethod);
    }

    public function valdiate(string $paymentMethod): bool
    {
        return $this->validate($paymentMethod);
    }
}
