<?php

declare(strict_types=1);

namespace App\Tests\Integration\Controller;

use App\Service\Order\OrderCheckout;
use App\Service\Order\OrderCheckoutResult;
use App\Service\Payment\PaypalCallbackSignature;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PaymentControllerIntegrationTest extends WebTestCase
{
    public function testBasketPaymentRedirectsToOrderWhenCheckoutSucceeds(): void
    {
        $client = static::createClient();

        $orderCheckout = $this->createMock(OrderCheckout::class);
        $orderCheckout
            ->expects(self::once())
            ->method('finalizeOrder')
            ->willReturn(OrderCheckoutResult::success('Order received.'));
        static::getContainer()->set(OrderCheckout::class, $orderCheckout);

        $crawler = $client->request('GET', '/basket/payment?addressId=42');
        $form = $crawler->filter('form')->form([
            'payment_form[paymentMethod]' => 'credit_card',
            'payment_form[addressId]' => '42',
            'payment_form[idempotencyKey]' => 'idem-controller-success',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/order');
    }

    public function testBasketPaymentRedirectsToProviderWhenCheckoutReturnsRedirectUrl(): void
    {
        $client = static::createClient();

        $orderCheckout = $this->createMock(OrderCheckout::class);
        $orderCheckout
            ->expects(self::once())
            ->method('finalizeOrder')
            ->willReturn(OrderCheckoutResult::redirect('https://paypal.example.test/approve'));
        static::getContainer()->set(OrderCheckout::class, $orderCheckout);

        $crawler = $client->request('GET', '/basket/payment?addressId=43');
        $form = $crawler->filter('form')->form([
            'payment_form[paymentMethod]' => 'paypal',
            'payment_form[addressId]' => '43',
            'payment_form[idempotencyKey]' => 'idem-controller-redirect',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('https://paypal.example.test/approve');
    }

    public function testPaypalSuccessRedirectsToOrderOnSuccessfulCapture(): void
    {
        $client = static::createClient();
        $idempotencyKey = 'idem-paypal-success';
        $signature = static::getContainer()->get(PaypalCallbackSignature::class)->sign($idempotencyKey);

        $orderCheckout = $this->createMock(OrderCheckout::class);
        $orderCheckout
            ->expects(self::once())
            ->method('completePaypalOrder')
            ->with('provider-success', $idempotencyKey)
            ->willReturn(OrderCheckoutResult::success('captured'));
        static::getContainer()->set(OrderCheckout::class, $orderCheckout);

        $client->request(
            'GET',
            '/basket/payment/paypal/success?token=provider-success&ik=' . rawurlencode($idempotencyKey) . '&sig=' . rawurlencode($signature)
        );

        self::assertResponseRedirects('/order');
    }

    public function testPaypalSuccessRedirectsBackToBasketOnFailure(): void
    {
        $client = static::createClient();
        $idempotencyKey = 'idem-paypal-failure';
        $signature = static::getContainer()->get(PaypalCallbackSignature::class)->sign($idempotencyKey);

        $orderCheckout = $this->createMock(OrderCheckout::class);
        $orderCheckout
            ->expects(self::once())
            ->method('completePaypalOrder')
            ->with('provider-failure', $idempotencyKey)
            ->willReturn(OrderCheckoutResult::failure('capture failed'));
        static::getContainer()->set(OrderCheckout::class, $orderCheckout);

        $client->request(
            'GET',
            '/basket/payment/paypal/success?token=provider-failure&ik=' . rawurlencode($idempotencyKey) . '&sig=' . rawurlencode($signature)
        );

        self::assertResponseRedirects('/basket');
    }

    public function testPaypalCancelRedirectsBackToBasket(): void
    {
        $client = static::createClient();
        $idempotencyKey = 'idem-paypal-cancel';
        $signature = static::getContainer()->get(PaypalCallbackSignature::class)->sign($idempotencyKey);

        $orderCheckout = $this->createMock(OrderCheckout::class);
        $orderCheckout
            ->expects(self::once())
            ->method('cancelPaypalOrder')
            ->with('provider-cancel', $idempotencyKey)
            ->willReturn(OrderCheckoutResult::failure('cancelled'));
        static::getContainer()->set(OrderCheckout::class, $orderCheckout);

        $client->request(
            'GET',
            '/basket/payment/paypal/cancel?token=provider-cancel&ik=' . rawurlencode($idempotencyKey) . '&sig=' . rawurlencode($signature)
        );

        self::assertResponseRedirects('/basket');
    }

    public function testPaypalSuccessRejectsInvalidCallbackSignature(): void
    {
        $client = static::createClient();

        $orderCheckout = $this->createMock(OrderCheckout::class);
        $orderCheckout->expects(self::never())->method('completePaypalOrder');
        static::getContainer()->set(OrderCheckout::class, $orderCheckout);

        $client->request('GET', '/basket/payment/paypal/success?token=provider-invalid&ik=idem-invalid&sig=invalid');

        self::assertResponseRedirects('/basket');
    }
}
