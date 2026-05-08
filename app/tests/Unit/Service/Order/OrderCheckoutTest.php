<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Order;

use App\Entity\Order;
use App\Exceptions\CheckoutValidationException;
use App\Message\FinalizeOrderMessage;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\Basket\IBasketManager;
use App\Service\Order\IOrderArticleSaver;
use App\Service\Order\IOrderSaver;
use App\Service\Order\IOrderStateManager;
use App\Service\Order\IStockReservationManager;
use App\Service\Order\OrderCheckout;
use App\Service\Payment\IPayment;
use App\Service\Payment\PaypalPayment;
use App\Service\Promotion\IPromotionManager;
use App\Service\Promotion\PromotionCalculationResult;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\Messenger\MessageBusInterface;

final class OrderCheckoutTest extends TestCase
{
    public function testFinalizeOrderFailsWhenBasketIsEmpty(): void
    {
        $basketManager = $this->createMock(IBasketManager::class);
        $basketManager->method('getBasketProducts')->willReturn([]);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::never())->method('findOneByIdempotencyKey');

        $checkout = $this->createCheckout(
            basketManager: $basketManager,
            orderRepository: $orderRepository
        );

        $result = $checkout->finalizeOrder($this->createPayment(), 'addr-1', 'idem-1');

        self::assertFalse($result->isSuccess());
        self::assertSame('Your basket is empty.', $result->getMessage());
    }

    public function testFinalizeOrderFailsWhenIdempotencyKeyMissing(): void
    {
        $basketManager = $this->createMock(IBasketManager::class);
        $basketManager->method('getBasketProducts')->willReturn(['products' => [1 => 1]]);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::never())->method('findOneByIdempotencyKey');

        $checkout = $this->createCheckout(
            basketManager: $basketManager,
            orderRepository: $orderRepository
        );

        $result = $checkout->finalizeOrder($this->createPayment(), 'addr-1', '   ');

        self::assertFalse($result->isSuccess());
        self::assertSame('Checkout token is missing. Please refresh and retry.', $result->getMessage());
    }

    public function testFinalizeOrderReturnsExistingPaypalPendingResult(): void
    {
        $basketManager = $this->createMock(IBasketManager::class);
        $basketManager->method('getBasketProducts')->willReturn(['products' => [1 => 1]]);
        $basketManager->expects(self::never())->method('resetBasket');

        $existingOrder = $this->createOrder('Paypal', Order::STATUS_PENDING_PAYMENT);
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneByIdempotencyKey')
            ->with('idem-2')
            ->willReturn($existingOrder);

        $checkout = $this->createCheckout(
            basketManager: $basketManager,
            orderRepository: $orderRepository
        );

        $result = $checkout->finalizeOrder($this->createPayment(), 'addr-2', 'idem-2');

        self::assertFalse($result->isSuccess());
        self::assertSame('Order already exists. Please finish payment in PayPal.', $result->getMessage());
    }

    public function testFinalizeOrderReturnsExistingOrderSuccessAndResetsBasket(): void
    {
        $basketManager = $this->createMock(IBasketManager::class);
        $basketManager->method('getBasketProducts')->willReturn(['products' => [1 => 1]]);
        $basketManager->expects(self::once())->method('resetBasket');

        $existingOrder = $this->createOrder('CreditCard', Order::STATUS_PENDING_PAYMENT);
        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneByIdempotencyKey')
            ->with('idem-3')
            ->willReturn($existingOrder);

        $checkout = $this->createCheckout(
            basketManager: $basketManager,
            orderRepository: $orderRepository
        );

        $result = $checkout->finalizeOrder($this->createPayment(), 'addr-3', 'idem-3');

        self::assertTrue($result->isSuccess());
        self::assertSame('Order is already being processed.', $result->getMessage());
    }

    public function testFinalizeOrderFailsWhenCheckoutLockCannotBeAcquired(): void
    {
        $basketManager = $this->createMock(IBasketManager::class);
        $basketManager->method('getBasketProducts')->willReturn(['products' => [1 => 1]]);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneByIdempotencyKey')
            ->with('idem-4')
            ->willReturn(null);

        $lockFactory = new LockFactory(new InMemoryStore());
        $occupiedLock = $lockFactory->createLock('checkout:address:addr-4', 30.0);
        self::assertTrue($occupiedLock->acquire());

        $checkout = $this->createCheckout(
            basketManager: $basketManager,
            orderRepository: $orderRepository,
            lockFactory: $lockFactory
        );

        $result = $checkout->finalizeOrder($this->createPayment(), 'addr-4', 'idem-4');

        $occupiedLock->release();

        self::assertFalse($result->isSuccess());
        self::assertSame('Another checkout is already in progress. Please retry in a moment.', $result->getMessage());
    }

    public function testFinalizeOrderReturnsValidationFailureWhenOrderSaverRejectsAddressOwnership(): void
    {
        $basketManager = $this->createMock(IBasketManager::class);
        $basketManager->method('getBasketProducts')->willReturn(['products' => [10 => 2]]);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::exactly(2))
            ->method('findOneByIdempotencyKey')
            ->with('idem-5')
            ->willReturnOnConsecutiveCalls(null, null);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');
        $connection->expects(self::never())->method('commit');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);

        $orderSaver = $this->createMock(IOrderSaver::class);
        $orderSaver
            ->expects(self::once())
            ->method('save')
            ->willThrowException(new CheckoutValidationException('Selected address does not belong to the current user.'));

        $orderArticleSaver = $this->createMock(IOrderArticleSaver::class);
        $orderArticleSaver->expects(self::never())->method('save');
        $stockReservationManager = $this->createMock(IStockReservationManager::class);
        $stockReservationManager->expects(self::once())->method('reserveForCheckout');
        $stockReservationManager->expects(self::once())->method('releaseForCheckout');

        $checkout = $this->createCheckout(
            entityManager: $entityManager,
            basketManager: $basketManager,
            orderSaver: $orderSaver,
            orderArticleSaver: $orderArticleSaver,
            orderRepository: $orderRepository,
            stockReservationManager: $stockReservationManager
        );

        $result = $checkout->finalizeOrder($this->createPayment(), 'addr-5', 'idem-5');

        self::assertFalse($result->isSuccess());
        self::assertSame('Selected address does not belong to the current user.', $result->getMessage());
    }

    public function testFinalizeOrderFailsWhenStockReservationCannotBeCreated(): void
    {
        $basketManager = $this->createMock(IBasketManager::class);
        $basketManager->method('getBasketProducts')->willReturn(['products' => [11 => 1]]);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::exactly(2))
            ->method('findOneByIdempotencyKey')
            ->with('idem-6')
            ->willReturnOnConsecutiveCalls(null, null);

        $stockReservationManager = $this->createMock(IStockReservationManager::class);
        $stockReservationManager
            ->expects(self::once())
            ->method('reserveForCheckout')
            ->willThrowException(new CheckoutValidationException('Product "Laptop" is temporarily unavailable in requested quantity.'));
        $stockReservationManager->expects(self::never())->method('releaseForCheckout');

        $orderSaver = $this->createMock(IOrderSaver::class);
        $orderSaver->expects(self::never())->method('save');

        $checkout = $this->createCheckout(
            basketManager: $basketManager,
            orderRepository: $orderRepository,
            orderSaver: $orderSaver,
            stockReservationManager: $stockReservationManager
        );

        $result = $checkout->finalizeOrder($this->createPayment(), 'addr-6', 'idem-6');

        self::assertFalse($result->isSuccess());
        self::assertSame('Product "Laptop" is temporarily unavailable in requested quantity.', $result->getMessage());
    }

    public function testCompletePaypalOrderFailsWhenIdempotencyKeyDoesNotMatch(): void
    {
        $order = $this->createOrder('Paypal', Order::STATUS_PENDING_PAYMENT);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneByProviderOrderId')
            ->with('provider-order-1')
            ->willReturn($order);

        $paypalPayment = $this->createMock(PaypalPayment::class);
        $paypalPayment->expects(self::never())->method('captureOrder');

        $orderStateManager = $this->createMock(IOrderStateManager::class);
        $orderStateManager->expects(self::never())->method('markAsPaid');
        $orderStateManager->expects(self::never())->method('markAsPaymentFailed');

        $checkout = $this->createCheckout(
            orderRepository: $orderRepository,
            paypalPayment: $paypalPayment,
            orderStateManager: $orderStateManager
        );

        $result = $checkout->completePaypalOrder('provider-order-1', 'another-idempotency-key');

        self::assertFalse($result->isSuccess());
        self::assertSame('PayPal callback does not match the original checkout request.', $result->getMessage());
    }

    public function testCancelPaypalOrderFailsWhenIdempotencyKeyDoesNotMatch(): void
    {
        $order = $this->createOrder('Paypal', Order::STATUS_PENDING_PAYMENT);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneByProviderOrderId')
            ->with('provider-order-2')
            ->willReturn($order);

        $orderStateManager = $this->createMock(IOrderStateManager::class);
        $orderStateManager->expects(self::never())->method('markAsPaymentFailed');

        $checkout = $this->createCheckout(
            orderRepository: $orderRepository,
            orderStateManager: $orderStateManager
        );

        $result = $checkout->cancelPaypalOrder('provider-order-2', 'another-idempotency-key');

        self::assertFalse($result->isSuccess());
        self::assertSame('PayPal callback does not match the original checkout request.', $result->getMessage());
    }

    public function testCompletePaypalOrderFailsWhenOrderAlreadyRefunded(): void
    {
        $order = $this->createOrder('Paypal', Order::STATUS_REFUNDED);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneByProviderOrderId')
            ->with('provider-order-3')
            ->willReturn($order);

        $paypalPayment = $this->createMock(PaypalPayment::class);
        $paypalPayment->expects(self::never())->method('captureOrder');

        $checkout = $this->createCheckout(
            orderRepository: $orderRepository,
            paypalPayment: $paypalPayment
        );

        $result = $checkout->completePaypalOrder('provider-order-3');

        self::assertFalse($result->isSuccess());
        self::assertSame('This order is already settled and can no longer be captured.', $result->getMessage());
    }

    public function testCancelPaypalOrderFailsWhenOrderAlreadyChargeback(): void
    {
        $order = $this->createOrder('Paypal', Order::STATUS_CHARGEBACK);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('findOneByProviderOrderId')
            ->with('provider-order-4')
            ->willReturn($order);

        $orderStateManager = $this->createMock(IOrderStateManager::class);
        $orderStateManager->expects(self::never())->method('markAsPaymentFailed');

        $checkout = $this->createCheckout(
            orderRepository: $orderRepository,
            orderStateManager: $orderStateManager
        );

        $result = $checkout->cancelPaypalOrder('provider-order-4');

        self::assertFalse($result->isSuccess());
        self::assertSame('This order is already settled and cannot be canceled.', $result->getMessage());
    }

    /**
     * @param array{
     *     entityManager?: EntityManagerInterface,
     *     basketManager?: IBasketManager,
     *     orderSaver?: IOrderSaver,
     *     orderArticleSaver?: IOrderArticleSaver,
     *     orderRepository?: OrderRepository,
     *     productRepository?: ProductRepository,
     *     orderStateManager?: IOrderStateManager,
     *     stockReservationManager?: IStockReservationManager,
     *     messageBus?: MessageBusInterface,
     *     logger?: LoggerInterface,
     *     requestStack?: RequestStack,
     *     lockFactory?: LockFactory,
     *     eventDispatcher?: EventDispatcherInterface,
     *     paypalPayment?: PaypalPayment,
     *     promotionManager?: IPromotionManager
     * } $overrides
     */
    private function createCheckout(
        ?EntityManagerInterface $entityManager = null,
        ?IBasketManager $basketManager = null,
        ?IOrderSaver $orderSaver = null,
        ?IOrderArticleSaver $orderArticleSaver = null,
        ?OrderRepository $orderRepository = null,
        ?ProductRepository $productRepository = null,
        ?IOrderStateManager $orderStateManager = null,
        ?IStockReservationManager $stockReservationManager = null,
        ?MessageBusInterface $messageBus = null,
        ?LoggerInterface $logger = null,
        ?RequestStack $requestStack = null,
        ?LockFactory $lockFactory = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?PaypalPayment $paypalPayment = null,
        ?IPromotionManager $promotionManager = null
    ): OrderCheckout {
        $entityManager ??= $this->createMock(EntityManagerInterface::class);
        $basketManager ??= $this->createMock(IBasketManager::class);
        $orderSaver ??= $this->createMock(IOrderSaver::class);
        $orderArticleSaver ??= $this->createMock(IOrderArticleSaver::class);
        $orderRepository ??= $this->createMock(OrderRepository::class);
        $productRepository ??= $this->createMock(ProductRepository::class);
        $orderStateManager ??= $this->createMock(IOrderStateManager::class);
        $stockReservationManager ??= $this->createMock(IStockReservationManager::class);
        $messageBus ??= $this->createMock(MessageBusInterface::class);
        $logger ??= $this->createMock(LoggerInterface::class);
        $requestStack ??= new RequestStack();
        $lockFactory ??= new LockFactory(new InMemoryStore());
        $eventDispatcher ??= $this->createMock(EventDispatcherInterface::class);
        $paypalPayment ??= $this->createMock(PaypalPayment::class);
        $promotionManager ??= $this->createMock(IPromotionManager::class);
        $promotionManager
            ->method('resolveCheckoutAmounts')
            ->willReturnCallback(
                static fn (?string $promotionCode, float $subtotal): PromotionCalculationResult => PromotionCalculationResult::withoutPromotion($subtotal)
            );

        return new OrderCheckout(
            $entityManager,
            $basketManager,
            $orderSaver,
            $orderArticleSaver,
            $orderRepository,
            $productRepository,
            $orderStateManager,
            $stockReservationManager,
            $messageBus,
            $logger,
            $requestStack,
            $lockFactory,
            $eventDispatcher,
            $paypalPayment,
            $promotionManager
        );
    }

    private function createPayment(string $name = 'credit_card'): IPayment
    {
        $payment = $this->createMock(IPayment::class);
        $payment->method('getPaymentName')->willReturn($name);

        return $payment;
    }

    private function createOrder(string $payment, string $status): Order
    {
        return Order::create(15.0, $payment, $status, new \DateTimeImmutable(), null, 'existing-idempotency');
    }
}
