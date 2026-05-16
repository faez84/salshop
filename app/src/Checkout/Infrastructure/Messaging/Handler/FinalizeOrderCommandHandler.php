<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Handler;

use App\Checkout\Application\Port\IOrderStateManager;
use App\Checkout\Application\Port\Payment\PaymentGatewayResolver;
use App\Checkout\Application\Port\Persistence\IOrderRepository;
use App\Checkout\Application\Port\Persistence\IPrimaryConnectionSwitcher;
use App\Checkout\Domain\Entity\Order;
use App\Checkout\Domain\Event\OrderCreated;
use App\Checkout\Infrastructure\Messaging\Command\FinalizeOrderCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Throwable;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final class FinalizeOrderCommandHandler
{
    public function __construct(
        private readonly IOrderRepository $orderRepository,
        private readonly PaymentGatewayResolver $paymentGatewayResolver,
        private readonly IOrderStateManager $orderStateManager,
        private readonly IPrimaryConnectionSwitcher $primaryConnectionSwitcher,
        private readonly LockFactory $lockFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        #[Autowire(service: 'monolog.logger.checkout')]
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(FinalizeOrderCommand $command): void
    {
        $orderId = $command->getOrderId();
        if ($orderId <= 0) {
            $this->logger->warning('FinalizeOrderCommand ignored: invalid order id.', [
                'orderId' => $orderId,
            ]);

            return;
        }

        $finalizeLock = $this->lockFactory->createLock(sprintf('finalize-order:%d', $orderId), 60.0);
        if (!$finalizeLock->acquire()) {
            $this->logger->warning('FinalizeOrderCommand ignored: finalize lock acquisition failed.', [
                'orderId' => $orderId,
            ]);

            return;
        }

        try {
            $this->primaryConnectionSwitcher->forcePrimaryConnection();

            $doctrineOrder = $this->orderRepository->findById($orderId);
            if (null === $doctrineOrder) {
                $this->logger->warning('FinalizeOrderCommand ignored: order not found.', [
                    'orderId' => $orderId,
                ]);

                return;
            }

            $order = Order::fromPersistence($doctrineOrder);
            if ($order->isFinished() || $order->isPaymentFailed() || $order->isSettled()) {
                $this->logger->info('FinalizeOrderCommand ignored: order already finalized.', [
                    'orderId' => $orderId,
                    'status' => $order->getStatus(),
                ]);

                return;
            }

            $paymentMethod = (string) ($order->getPayment() ?? '');
            $paymentRequestId = ($order->getIdempotencyKey() ?? ('order-' . $orderId)) . '-pay';

            try {
                $paymentGateway = $this->paymentGatewayResolver->getPaymentMethod($paymentMethod);
                $isPaymentSuccessful = $paymentGateway->executePayment($paymentRequestId);
            } catch (Throwable $exception) {
                if ($this->isRetryablePaymentException($exception)) {
                    $this->logger->warning('FinalizeOrderCommand transient payment failure. Scheduling retry.', [
                        'orderId' => $orderId,
                        'paymentMethod' => $paymentMethod,
                        'paymentRequestId' => $paymentRequestId,
                        'exceptionClass' => $exception::class,
                        'exceptionMessage' => $exception->getMessage(),
                    ]);

                    throw new RecoverableMessageHandlingException(
                        sprintf('Transient payment failure while finalizing order %d.', $orderId),
                        0,
                        $exception
                    );
                }

                $this->logger->error('FinalizeOrderCommand payment execution failed.', [
                    'orderId' => $orderId,
                    'paymentMethod' => $paymentMethod,
                    'paymentRequestId' => $paymentRequestId,
                    'exceptionClass' => $exception::class,
                    'exceptionMessage' => $exception->getMessage(),
                ]);
                $this->orderStateManager->markAsPaymentFailed($order, true);

                return;
            }

            if (!$isPaymentSuccessful) {
                $this->orderStateManager->markAsPaymentFailed($order, true);
                $this->logger->warning('FinalizeOrderCommand marked order as payment_failed.', [
                    'orderId' => $orderId,
                    'paymentMethod' => $paymentMethod,
                    'paymentRequestId' => $paymentRequestId,
                ]);

                return;
            }

            if (!$this->orderStateManager->markAsPaid($order)) {
                $this->logger->warning('FinalizeOrderCommand could not apply "pay" transition.', [
                    'orderId' => $orderId,
                    'status' => $order->getStatus(),
                    'paymentRequestId' => $paymentRequestId,
                ]);

                return;
            }

            $this->eventDispatcher->dispatch(new OrderCreated($order->getId()));
            $this->logger->info('FinalizeOrderCommand completed successfully.', [
                'orderId' => $orderId,
                'paymentMethod' => $paymentMethod,
                'paymentRequestId' => $paymentRequestId,
            ]);
        } finally {
            $finalizeLock->release();
        }
    }

    private function isRetryablePaymentException(Throwable $exception): bool
    {
        if ($exception instanceof TransportExceptionInterface) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'timed out')
            || str_contains($message, 'temporar')
            || str_contains($message, 'connection refused')
            || str_contains($message, 'could not resolve host')
            || str_contains($message, 'dns')
            || str_contains($message, 'network');
    }
}
