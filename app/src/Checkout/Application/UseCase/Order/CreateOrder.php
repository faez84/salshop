<?php

declare(strict_types=1);

namespace App\Checkout\Application\UseCase\Order;

use App\Checkout\Application\Port\IOrderStateManager;
use App\Checkout\Application\Port\Persistence\IOrderRepository;
use App\Checkout\Application\Port\Persistence\IPrimaryConnectionSwitcher;
use App\Checkout\Application\Port\Persistence\OrderCreated;
use App\Checkout\Domain\Entity\Order;
use App\Checkout\Domain\ValueObject\OrderCheckoutResult;
use App\Checkout\Infrastructure\Payment\PaypalPayment;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;

final class CreateOrder
{
    public function __construct(
       private readonly LoggerInterface $logger,
       private readonly LockFactory $lockFactory,
       private readonly IOrderRepository $orderRepository,
       private readonly IOrderStateManager $orderStateManager,
       private readonly PaypalPayment $paypalPayment,
       private readonly IPrimaryConnectionSwitcher $primaryConnectionSwitcher,
       private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function completePaypalOrder(string $providerOrderId, ?string $expectedIdempotencyKey = null): OrderCheckoutResult
    {
        $this->primaryConnectionSwitcher->forcePrimaryConnection();

        $providerOrderId = trim($providerOrderId);
        if ('' === $providerOrderId) {
            return OrderCheckoutResult::failure('Missing PayPal order token.');
        }

        $this->logger->info('PayPal success callback received.', [
            'providerOrderId' => $providerOrderId,
            'expectedIdempotencyKey' => $expectedIdempotencyKey,
        ]);

        $callbackLock = $this->lockFactory->createLock('paypal-callback:' . $providerOrderId, 10.0);
        if (!$callbackLock->acquire()) {
            return OrderCheckoutResult::failure('Another PayPal callback is currently being processed. Please retry.');
        }

        try {
            $order = $this->orderRepository->findOneByProviderOrderId($providerOrderId);
            if (!$order instanceof Order) {
                return OrderCheckoutResult::failure('PayPal order was not found.');
            }

            $normalizedExpectedIdempotencyKey = trim((string) $expectedIdempotencyKey);
            if ('' !== $normalizedExpectedIdempotencyKey && $normalizedExpectedIdempotencyKey !== (string) $order->getIdempotencyKey()) {
                return OrderCheckoutResult::failure('PayPal callback does not match the original checkout request.');
            }

            if ($order->isFinished()) {
                return OrderCheckoutResult::success('Order was already completed.');
            }

            if ($order->isPaymentFailed()) {
                return OrderCheckoutResult::failure('This order payment was already canceled or failed.');
            }

            if ($order->isSettled()) {
                return OrderCheckoutResult::failure('This order is already settled and can no longer be captured.');
            }

            if (!$order->isPaypalPayment()) {
                return OrderCheckoutResult::failure('The selected order is not a PayPal checkout.');
            }

            $captureRequestId = ($order->getIdempotencyKey() ?? ('order-' . $order->getId())) . '-capture';
            try {
                $captured = $this->paypalPayment->captureOrder($providerOrderId, $captureRequestId);
            } catch (\Throwable $exception) {
                $this->logger->error('PayPal capture failed due to exception.', [
                    'orderId' => $order->getId(),
                    'providerOrderId' => $providerOrderId,
                    'exceptionMessage' => $exception->getMessage(),
                ]);

                return OrderCheckoutResult::failure('Could not verify PayPal payment. Please retry in a moment.');
            }

            if (!$captured) {
                 $this->orderStateManager->markAsPaymentFailed($order, true);

                return OrderCheckoutResult::failure('PayPal payment was not completed.');
            }
            try {
                $orderWasMarkedPaid = $this->orderStateManager->markAsPaid($order);
            } catch (\Throwable $exception) {
                $this->logger->error('Could not apply order "pay" transition after PayPal capture.', [
                    'orderId' => $order->getId(),
                    'providerOrderId' => $providerOrderId,
                    'exceptionMessage' => $exception->getMessage(),
                ]);

                return OrderCheckoutResult::failure('Payment was captured, but local order update failed. Please contact support.');
            }
            if (!$orderWasMarkedPaid) {
                return OrderCheckoutResult::failure('This order can no longer be completed from its current status.');
            }

            $this->eventDispatcher->dispatch(new OrderCreated($order->getId()));
            $this->logger->info('PayPal order captured and marked as paid.', [
                'orderId' => $order->getId(),
                'providerOrderId' => $providerOrderId,
            ]);

            return OrderCheckoutResult::success('Order received. PayPal payment completed.');
        } finally {
            $callbackLock->release();
        }
    }
}
