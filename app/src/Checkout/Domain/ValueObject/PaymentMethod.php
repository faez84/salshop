<?php

declare(strict_types=1);

namespace App\Checkout\Domain\ValueObject;

enum PaymentMethod: string
{
    case CREDIT_CARD = 'credit_card';
    case PAYPAL = 'paypal';

    public static function fromInput(string $value): ?self
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(['-', '_', ' '], '', $normalized);

        return match ($normalized) {
            'creditcard' => self::CREDIT_CARD,
            'paypal' => self::PAYPAL,
            default => null,
        };
    }

    public static function isPaypal(?string $value): bool
    {
        if (null === $value) {
            return false;
        }

        return self::PAYPAL === self::fromInput($value);
    }

    /**
     * @return array<string, string>
     */
    public static function formChoices(): array
    {
        return [
            'Credit Card' => self::CREDIT_CARD->value,
            'PayPal' => self::PAYPAL->value,
        ];
    }
}
