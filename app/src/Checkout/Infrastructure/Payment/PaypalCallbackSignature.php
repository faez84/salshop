<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Payment;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class PaypalCallbackSignature
{
    public function __construct(
        #[Autowire('%env(APP_SECRET)%')]
        private readonly string $appSecret
    ) {
    }

    public function sign(string $idempotencyKey): string
    {
        return hash_hmac('sha256', $idempotencyKey, $this->appSecret);
    }

    public function isValid(string $idempotencyKey, string $signature): bool
    {
        if ('' === trim($idempotencyKey) || '' === trim($signature)) {
            return false;
        }

        return hash_equals($this->sign($idempotencyKey), $signature);
    }
}
