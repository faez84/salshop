<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Order;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use App\Service\Order\OrderStateManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Workflow\WorkflowInterface;

final class OrderStateManagerTest extends TestCase
{
    public function testMarkAsRefundedRestoresProductQuantity(): void
    {
        [$order, $product] = $this->createOrderWithProduct(Order::STATUS_FINISHED, 2, 3);
        $stateManager = $this->createStateManager('refund');

        $result = $stateManager->markAsRefunded($order);

        self::assertTrue($result);
        self::assertSame(5, $product->getQuantity());
    }

    public function testMarkAsChargebackRestoresProductQuantityWhenOrderWasFinished(): void
    {
        [$order, $product] = $this->createOrderWithProduct(Order::STATUS_FINISHED, 4, 2);
        $stateManager = $this->createStateManager('chargeback');

        $result = $stateManager->markAsChargeback($order);

        self::assertTrue($result);
        self::assertSame(6, $product->getQuantity());
    }

    public function testMarkAsChargebackDoesNotRestoreProductQuantityWhenOrderWasAlreadyRefunded(): void
    {
        [$order, $product] = $this->createOrderWithProduct(Order::STATUS_REFUNDED, 4, 2);
        $stateManager = $this->createStateManager('chargeback');

        $result = $stateManager->markAsChargeback($order);

        self::assertTrue($result);
        self::assertSame(4, $product->getQuantity());
    }

    private function createStateManager(string $transition): OrderStateManager
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->expects(self::atLeastOnce())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $workflow = $this->createMock(WorkflowInterface::class);
        $workflow->expects(self::once())
            ->method('can')
            ->with(self::isInstanceOf(Order::class), $transition)
            ->willReturn(true);
        $workflow->expects(self::once())
            ->method('apply')
            ->with(self::isInstanceOf(Order::class), $transition);

        return new OrderStateManager($entityManager, $workflow);
    }

    /**
     * @return array{Order, Product}
     */
    private function createOrderWithProduct(string $status, int $initialProductQuantity, int $orderedQuantity): array
    {
        $order = Order::create(
            15.0,
            'credit_card',
            $status,
            new \DateTimeImmutable(),
            null,
            'idem-state-manager'
        );
        $product = Product::create(
            'Test Product',
            10.0,
            $initialProductQuantity,
            null,
            null,
            null,
            'SKU-1',
            null
        );
        $orderProduct = OrderProduct::create($orderedQuantity, 10.0, $order, $product);
        $order->addOrderProduct($orderProduct);

        return [$order, $product];
    }
}
