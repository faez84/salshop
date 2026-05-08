<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Workflow;

use App\Checkout\Application\Port\IOrderStateManager;
use App\Checkout\Domain\Entity\Order;
use App\Checkout\Infrastructure\Persistence\Doctrine\Order as DoctrineOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;
use Throwable;

final class OrderStateManager implements IOrderStateManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'state_machine.order_checkout')]
        private readonly WorkflowInterface $orderStateMachine
    ) {
    }

    public function markAsPaid(Order $order): bool
    {
        return $this->applyTransition($order->toPersistence(), 'pay');
    }

    public function markAsPaymentFailed(Order $order, bool $restoreReservedProductQuantities = true): bool
    {
        $doctrineOrder = $order->toPersistence();

        return $this->applyTransition(
            $doctrineOrder,
            'fail',
            function () use ($doctrineOrder, $restoreReservedProductQuantities): void {
                if (!$restoreReservedProductQuantities) {
                    return;
                }

                $this->restoreOrderedProductQuantities($doctrineOrder);
            }
        );
    }

    public function markAsRefunded(Order $order): bool
    {
        $doctrineOrder = $order->toPersistence();

        return $this->applyTransition(
            $doctrineOrder,
            'refund',
            fn () => $this->restoreOrderedProductQuantities($doctrineOrder)
        );
    }

    public function markAsChargeback(Order $order): bool
    {
        $doctrineOrder = $order->toPersistence();
        $isAlreadyRefunded = DoctrineOrder::STATUS_REFUNDED === (string) $doctrineOrder->getStatus();

        return $this->applyTransition(
            $doctrineOrder,
            'chargeback',
            function () use ($doctrineOrder, $isAlreadyRefunded): void {
                if ($isAlreadyRefunded) {
                    return;
                }

                $this->restoreOrderedProductQuantities($doctrineOrder);
            }
        );
    }

    private function applyTransition(DoctrineOrder $order, string $transition, ?callable $beforeFlush = null): bool
    {
        if (!$this->orderStateMachine->can($order, $transition)) {
            return false;
        }

        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $this->orderStateMachine->apply($order, $transition);
            if (null !== $beforeFlush) {
                $beforeFlush();
            }

            $this->entityManager->persist($order);
            $this->entityManager->flush();
            $conn->commit();
        } catch (Throwable $exception) {
            $conn->rollBack();

            throw $exception;
        }

        return true;
    }

    private function restoreOrderedProductQuantities(DoctrineOrder $order): void
    {
        foreach ($order->getOrderProducts() as $orderProduct) {
            $product = $orderProduct->getPproduct();
            if (null === $product) {
                continue;
            }

            $product->setQuantity($product->getQuantity() + (int) $orderProduct->getAmount());
            $this->entityManager->persist($product);
        }
    }
}
