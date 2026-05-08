<?php

declare(strict_types=1);

namespace App\Checkout\Application\UseCase\Order;

use App\Checkout\Application\Port\IOrderStateManager;
use App\Checkout\Application\Port\Persistence\IOrderRepository;
use App\Checkout\Application\Port\Persistence\IPrimaryConnectionSwitcher;
use App\Checkout\Domain\Entity\Order;
use App\Checkout\Domain\ValueObject\OrderCheckoutResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\Lock\LockFactory;

final class CancelOrder
{
    public function __construct(
        private readonly IOrderRepository $orderRepository,
        private readonly IOrderStateManager $orderStateManager,
        private readonly LockFactory $lockFactory,
        private readonly LoggerInterface $logger,
        private readonly IPrimaryConnectionSwitcher $primaryConnectionSwitcher,
    ) {
    }
    public function cancelPaypalOrder(string $providerOrderId, ?string $expectedIdempotencyKey = null): OrderCheckoutResult
    {
        $this->primaryConnectionSwitcher->forcePrimaryConnection();

        $providerOrderId = trim($providerOrderId);
        if ('' === $providerOrderId) {
            return OrderCheckoutResult::failure('Missing PayPal order token.');
        }

        $this->logger->info('PayPal cancel callback received.', [
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
                return OrderCheckoutResult::failure('No matching order was found for this PayPal token.');
            }

            $normalizedExpectedIdempotencyKey = trim((string) $expectedIdempotencyKey);
            if ('' !== $normalizedExpectedIdempotencyKey && $normalizedExpectedIdempotencyKey !== (string) $order->getIdempotencyKey()) {
                return OrderCheckoutResult::failure('PayPal callback does not match the original checkout request.');
            }

            if ($order->isFinished()) {
                return OrderCheckoutResult::failure('This order was already paid and cannot be canceled.');
            }

            if ($order->isPaymentFailed()) {
                return OrderCheckoutResult::failure('This order was already canceled.');
            }

            if ($order->isSettled()) {
                return OrderCheckoutResult::failure('This order is already settled and cannot be canceled.');
            }

            $this->markOrderAsPaymentFailedAndRestoreQuantities($order);
            $this->logger->info('PayPal order marked as payment failed after cancel callback.', [
                'orderId' => $order->getId(),
                'providerOrderId' => $providerOrderId,
            ]);

            return OrderCheckoutResult::failure('PayPal checkout was canceled.');
        } finally {
            $callbackLock->release();
        }
    }

    private function markOrderAsPaymentFailedAndRestoreQuantities(Order $order): void
    {
        $this->orderStateManager->markAsPaymentFailed($order, true);
    }
}
