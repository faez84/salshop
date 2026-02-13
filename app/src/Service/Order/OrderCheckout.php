<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Order;
use App\Message\FinalizeOrderMessage;
use App\Service\BasketManager;
use App\Service\Payment\IPayment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class OrderCheckout
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        protected BasketManager $basketManager,
        protected OrderSaver $orderSaver,
        protected OrderArticleSaver $orderArticleSaver,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function finalizeOrder(IPayment $payment, string $addressId): OrderCheckoutResult
    {
        $basket = $this->basketManager->getBasketProducts();
        if ([] === $basket || !isset($basket['products'], $basket['cost'])) {
            return OrderCheckoutResult::failure('Your basket is empty.');
        }

        $order = null;

        try {
            $order = $this->createPendingOrder($payment, $addressId, $basket);
        } catch (Throwable $exception) {
            $this->logger->error('Order creation failed before payment.', [
                'addressId' => $addressId,
                'paymentMethod' => $payment->getPaymentName(),
                'exceptionMessage' => $exception->getMessage(),
            ]);

            return OrderCheckoutResult::failure('Error during order creation. Please try again.');
        }

        try {
            $this->messageBus->dispatch(new FinalizeOrderMessage((int)$order->getId()));
        } catch (Throwable $exception) {
            try {
                $this->markOrderAsFailedAfterDispatchError($order);
            } catch (Throwable $markFailedException) {
                $this->logger->error('Could not mark order as payment_failed after dispatch failure.', [
                    'orderId' => $order->getId(),
                    'exceptionMessage' => $markFailedException->getMessage(),
                ]);
            }

            $this->logger->error('Could not dispatch async order finalization message.', [
                'orderId' => $order->getId(),
                'addressId' => $addressId,
                'paymentMethod' => $payment->getPaymentName(),
                'exceptionMessage' => $exception->getMessage(),
            ]);

            return OrderCheckoutResult::failure('Error finalizing order. Please contact support.');
        }

        $this->basketManager->resetBasket();

        return OrderCheckoutResult::success('Order received. Payment is being processed.');
    }

    /**
     * @param array<string, mixed> $basket
     */
    private function createPendingOrder(IPayment $payment, string $addressId, array $basket): Order
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $order = $this->orderSaver->save($payment->getPaymentName(), $addressId, (float)$basket['cost']);
            $this->orderArticleSaver->save($order, $basket['products']);
            $this->entityManager->flush();
            $conn->commit();

            return $order;
        } catch (Throwable $exception) {
            $conn->rollBack();

            throw $exception;
        }
    }

    private function markOrderAsFailedAfterDispatchError(Order $order): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $order->setStatus(Order::STATUS_PAYMENT_FAILED);
            foreach ($order->getOrderProducts() as $orderProduct) {
                $product = $orderProduct->getPproduct();
                if (null === $product) {
                    continue;
                }

                $product->setQuantity($product->getQuantity() + (int)$orderProduct->getAmount());
                $this->entityManager->persist($product);
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $conn->commit();
        } catch (Throwable $exception) {
            $conn->rollBack();

            throw $exception;
        }
    }
}
