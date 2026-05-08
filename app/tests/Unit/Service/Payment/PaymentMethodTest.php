<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Payment;

use App\Service\Payment\PaymentMethod;
use PHPUnit\Framework\TestCase;

final class PaymentMethodTest extends TestCase
{
    /**
     * @dataProvider fromInputProvider
     */
    public function testFromInput(?PaymentMethod $expected, string $input): void
    {
        self::assertSame($expected, PaymentMethod::fromInput($input));
    }

    public function testIsPaypalSupportsLegacyAndCanonicalValues(): void
    {
        self::assertTrue(PaymentMethod::isPaypal('Paypal'));
        self::assertTrue(PaymentMethod::isPaypal('paypal'));
        self::assertFalse(PaymentMethod::isPaypal('CreditCard'));
        self::assertFalse(PaymentMethod::isPaypal(null));
    }

    public function testFormChoicesExposeExpectedValues(): void
    {
        self::assertSame(
            [
                'Credit Card' => 'credit_card',
                'PayPal' => 'paypal',
            ],
            PaymentMethod::formChoices()
        );
    }

    /**
     * @return array<string, array{0: ?PaymentMethod, 1: string}>
     */
    public static function fromInputProvider(): array
    {
        return [
            'paypal canonical' => [PaymentMethod::PAYPAL, 'paypal'],
            'paypal legacy' => [PaymentMethod::PAYPAL, 'Paypal'],
            'credit card camel' => [PaymentMethod::CREDIT_CARD, 'CreditCard'],
            'credit card snake' => [PaymentMethod::CREDIT_CARD, 'credit_card'],
            'credit card kebab' => [PaymentMethod::CREDIT_CARD, 'credit-card'],
            'credit card with spaces' => [PaymentMethod::CREDIT_CARD, 'credit card'],
            'unknown' => [null, 'wire-transfer'],
        ];
    }
}
