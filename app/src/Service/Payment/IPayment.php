<?php

declare(strict_types=1);

namespace App\Service\Payment;

interface IPayment
{
    public function executePayment(): bool;
    public function getPaymentName(): string;

    public function setPayment(string $paymentName): void;
}
