<?php

declare(strict_types=1);

namespace App\Service\Order;

final class OrderCheckoutResult
{
    private function __construct(
        private readonly bool $success,
        private readonly string $message
    ) {
    }

    public static function success(string $message = 'Order finalized successfully.'): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
