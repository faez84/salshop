<?php

declare(strict_types=1);

namespace App\Checkout\Domain\ValueObject;

final class OrderCheckoutResult
{
    private function __construct(
        private readonly bool $success,
        private readonly string $message,
        private readonly ?string $redirectUrl
    ) {
    }

    public static function success(string $message = 'Order finalized successfully.'): self
    {
        return new self(true, $message, null);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message, null);
    }

    public static function redirect(string $redirectUrl, string $message = 'Redirecting to payment provider.'): self
    {
        return new self(true, $message, $redirectUrl);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function hasRedirectUrl(): bool
    {
        return null !== $this->redirectUrl && '' !== $this->redirectUrl;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }
}
