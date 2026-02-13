<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Order;
use App\Event\OrderEvent;
use App\Message\FinalizeOrderMessage;
use App\Repository\OrderRepository;
use App\Service\Payment\PaymentMethodFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

#[AsMessageHandler]
final class FinalizeOrderMessageHandler
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PaymentMethodFactory $paymentMethodFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(FinalizeOrderMessage $message): void
    {
        $order = $this->orderRepository->find($message->getOrderId());
        if (!$order instanceof Order) {
            $this->logger->warning('FinalizeOrderMessage received for non-existing order.', [
                'orderId' => $message->getOrderId(),
            ]);
            return;
        }

        if (in_array((string)$order->getStatus(), [Order::STATUS_FINISHED, Order::STATUS_PAYMENT_FAILED], true)) {
            return;
        }

        $paymentMethod = $this->paymentMethodFactory->getPaymentMethod((string)$order->getPayment());

        $paymentSucceeded = false;
        try {
            $paymentSucceeded = $paymentMethod->executePayment();
        } catch (Throwable $exception) {
            $this->logger->error('Payment execution threw in async handler.', [
                'orderId' => $order->getId(),
                'paymentMethod' => $order->getPayment(),
                'exceptionMessage' => $exception->getMessage(),
            ]);
        }

        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        try {
            if ($paymentSucceeded) {
                $order->setStatus(Order::STATUS_FINISHED);
            } else {
                $order->setStatus(Order::STATUS_PAYMENT_FAILED);
                $this->restoreReservedProductQuantities($order);
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $conn->commit();
        } catch (Throwable $exception) {
            $conn->rollBack();
            $this->logger->error('Async order finalization transaction failed.', [
                'orderId' => $order->getId(),
                'paymentSucceeded' => $paymentSucceeded,
                'exceptionMessage' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if ($paymentSucceeded) {
            $this->eventDispatcher->dispatch(new OrderEvent($order));
        }
    }

    private function restoreReservedProductQuantities(Order $order): void
    {
        foreach ($order->getOrderProducts() as $orderProduct) {
            $product = $orderProduct->getPproduct();
            if (null === $product) {
                continue;
            }

            $product->setQuantity($product->getQuantity() + (int)$orderProduct->getAmount());
            $this->entityManager->persist($product);
        }
    }
}
