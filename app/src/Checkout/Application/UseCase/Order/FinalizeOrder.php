<?php

declare(strict_types=1);

namespace App\Checkout\Application\UseCase\Order;

use App\Basket\Application\UseCase\ClearBasket;
use App\Basket\Application\UseCase\GetBasketProducts;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use App\Checkout\Application\Exceptions\CheckoutValidationException;
use App\Checkout\Application\Port\Inventory\IStockReservationManager;
use App\Checkout\Application\Port\IOrderArticleSaver;
use App\Checkout\Application\Port\IOrderSaver;
use App\Checkout\Application\Port\IOrderStateManager;
use App\Checkout\Application\Port\Messaging\ICommandBus;
use App\Checkout\Application\Port\Payment\PaymentGateway;
use App\Checkout\Application\Port\Persistence\IOrderRepository;
use App\Checkout\Application\Port\Persistence\IPrimaryConnectionSwitcher;
use App\Checkout\Application\Port\Persistence\IProductReadRepository;
use App\Checkout\Application\Port\Promotion\IPromotionManager;
use App\Checkout\Domain\Entity\Order;
use App\Checkout\Domain\ValueObject\OrderCheckoutResult;
use App\Checkout\Infrastructure\Messaging\Command\FinalizeOrderCommand;
use App\Checkout\Infrastructure\Payment\PaypalPayment;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

final class FinalizeOrder
{
     public function __construct(
        private readonly EntityManagerInterface $entityManager,
        protected GetBasketProducts $getBasketProducts,
        protected ClearBasket $clearBasket,
        protected IOrderSaver $orderSaver,
        protected IOrderArticleSaver $orderArticleSaver,
        private readonly IOrderRepository $orderRepository,
        private readonly IProductReadRepository $productRepository,
        private readonly IOrderStateManager $orderStateManager,
        private readonly IStockReservationManager $stockReservationManager,
        private readonly ICommandBus $commandBus,
        #[Autowire(service: 'monolog.logger.checkout')]
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
        private readonly LockFactory $lockFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly PaypalPayment $paypalPayment,
        private readonly IPromotionManager $promotionManager,
        private readonly IPrimaryConnectionSwitcher $primaryConnectionSwitcher,
    ) {
    }

    public function execute(
        PaymentGateway $payment,
        string $addressId,
        string $idempotencyKey,
        ?string $promotionCode = null,
        ?string $paypalReturnUrl = null,
        ?string $paypalCancelUrl = null
    ): OrderCheckoutResult {
        $this->primaryConnectionSwitcher->forcePrimaryConnection();

        $this->logger->info('Checkout finalization requested.', [
            'addressId' => $addressId,
            'paymentMethod' => $payment->getPaymentName(),
            'idempotencyKey' => $idempotencyKey,
            'promotionCode' => $promotionCode,
        ]);

        $basket = $this->getBasketProducts->getBasketProducts();
        if ([] === $basket || !isset($basket['products']) || [] === $basket['products']) {
            return OrderCheckoutResult::failure('Your basket is empty.');
        }

        $idempotencyKey = trim($idempotencyKey);
        if ('' === $idempotencyKey) {
            return OrderCheckoutResult::failure('Checkout token is missing. Please refresh and retry.');
        }

        $alreadyCreatedOrder = $this->orderRepository->findOneByIdempotencyKey($idempotencyKey);
        if ($alreadyCreatedOrder instanceof Order) {
            $this->logger->info('Checkout idempotency hit before lock acquisition.', [
                'idempotencyKey' => $idempotencyKey,
                'orderId' => $alreadyCreatedOrder->getId(),
            ]);

            return $this->buildExistingOrderResult($alreadyCreatedOrder);
        }

        $checkoutKey = $this->getCheckoutOwnerKey($addressId);
        $checkoutLock = $this->lockFactory->createLock(sprintf('checkout:%s', $checkoutKey), 10.0);
        if (!$checkoutLock->acquire()) {
            $this->logger->warning('Checkout lock acquisition failed.', [
                'checkoutKey' => $checkoutKey,
                'idempotencyKey' => $idempotencyKey,
            ]);

            return OrderCheckoutResult::failure('Another checkout is already in progress. Please retry in a moment.');
        }

        try {
            $alreadyCreatedOrder = $this->orderRepository->findOneByIdempotencyKey($idempotencyKey);
            if ($alreadyCreatedOrder instanceof Order) {
                $this->logger->info('Checkout idempotency hit after lock acquisition.', [
                    'idempotencyKey' => $idempotencyKey,
                    'orderId' => $alreadyCreatedOrder->getId(),
                ]);

                return $this->buildExistingOrderResult($alreadyCreatedOrder);
            }

            $stockReservationKey = sprintf('%s:%s', $checkoutKey, $idempotencyKey);
            $reservationCreated = false;
            $order = null;
            try {
                $this->stockReservationManager->reserveForCheckout($stockReservationKey, $basket['products']);
                $reservationCreated = true;
                $order = $this->createPendingOrder($payment, $addressId, $basket, $idempotencyKey, $promotionCode);
            } catch (CheckoutValidationException $exception) {
                return OrderCheckoutResult::failure($exception->getMessage());
            } catch (UniqueConstraintViolationException) {
                $alreadyCreatedOrder = $this->orderRepository->findOneByIdempotencyKey($idempotencyKey);
                if ($alreadyCreatedOrder instanceof Order) {
                    return $this->buildExistingOrderResult($alreadyCreatedOrder);
                }

                return OrderCheckoutResult::failure('Order already exists for this checkout request.');
            } catch (\Throwable $exception) {
                $this->logger->error('Order creation failed before payment.', [
                    'addressId' => $addressId,
                    'paymentMethod' => $payment->getPaymentName(),
                    'idempotencyKey' => $idempotencyKey,
                    'exceptionMessage' => $exception->getMessage(),
                ]);

                return OrderCheckoutResult::failure('Error during order creation. Please try again.');
            } finally {
                if ($reservationCreated) {
                    $this->stockReservationManager->releaseForCheckout($stockReservationKey);
                }
            }

            if ($payment instanceof PaypalPayment) {
                return $this->processPaypalCheckout($payment, $order, $idempotencyKey, $paypalReturnUrl, $paypalCancelUrl);
            }

            return $this->dispatchAsyncFinalization($order, $payment, $addressId, $idempotencyKey);
        } finally {
            $checkoutLock->release();
        }
    }

    private function processPaypalCheckout(
        PaypalPayment $paypalPayment,
        Order $order,
        string $idempotencyKey,
        ?string $paypalReturnUrl,
        ?string $paypalCancelUrl
    ): OrderCheckoutResult {
        if (null === $paypalReturnUrl || null === $paypalCancelUrl || '' === trim($paypalReturnUrl) || '' === trim($paypalCancelUrl)) {
            $this->markOrderAsPaymentFailedAndRestoreQuantities($order);

            return OrderCheckoutResult::failure('PayPal callback URLs are missing. Please contact support.');
        }

        try {
            $paypalOrder = $paypalPayment->createOrder(
                (float) $order->getCost(),
                'USD',
                $paypalReturnUrl,
                $paypalCancelUrl,
                $idempotencyKey . '-create'
            );
        } catch (\Throwable $exception) {
            $this->logger->error('PayPal order creation failed.', [
                'orderId' => $order->getId(),
                'idempotencyKey' => $idempotencyKey,
                'exceptionMessage' => $exception->getMessage(),
            ]);

            $this->markOrderAsPaymentFailedAndRestoreQuantities($order);

            return OrderCheckoutResult::failure('Could not initialize PayPal checkout. Please try again.');
        }

        try {
            $order->assignProviderOrderId($paypalOrder->getProviderOrderId());
            $this->entityManager->persist($order->toPersistence());
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            $this->logger->error('Could not persist PayPal provider order id locally.', [
                'orderId' => $order->getId(),
                'providerOrderId' => $paypalOrder->getProviderOrderId(),
                'exceptionMessage' => $exception->getMessage(),
            ]);

            $this->markOrderAsPaymentFailedAndRestoreQuantities($order);

            return OrderCheckoutResult::failure('Could not prepare PayPal checkout locally. Please try again.');
        }

        $this->clearBasket->clear();

        return OrderCheckoutResult::redirect($paypalOrder->getApprovalUrl(), 'Redirecting to PayPal.');
    }

    
    private function markOrderAsPaymentFailedAndRestoreQuantities(Order $order): void
    {
        $this->orderStateManager->markAsPaymentFailed($order, true);
    }


    /**
     * @param array<string, mixed> $basket
     */
    private function createPendingOrder(
        PaymentGateway $payment,
        string $addressId,
        array $basket,
        string $idempotencyKey,
        ?string $promotionCode
    ): Order {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            $basketProducts = $this->normalizeBasketProducts($basket['products'] ?? []);
            if ([] === $basketProducts) {
                throw new CheckoutValidationException('Your basket is empty.');
            }

            $productsById = $this->productRepository->findByIdsIndexed(array_keys($basketProducts));
            $subtotal = $this->calculateBasketCost($basketProducts, $productsById);
            $promotionCalculation = $this->promotionManager->resolveCheckoutAmounts($promotionCode, (float) $subtotal);
            $orderDta = $this->orderSaver->save(
                $payment->getPaymentName(),
                $addressId,
                $promotionCalculation->getFinalCost(),
                $idempotencyKey,
                $promotionCalculation->getPromotionCode(),
                $promotionCalculation->getDiscountAmount()
            );
            $order = Order::fromPersistence($orderDta);
            $this->orderArticleSaver->save($order->toPersistence(), $basketProducts, $productsById);
            $this->promotionManager->markPromotionAsUsed($promotionCalculation->getPromotion());
            $this->entityManager->flush();
            $conn->commit();

            return $order;
        } catch (\Throwable $exception) {
            $conn->rollBack();

            throw $exception;
        }
    }


    /**
     * @param array<int|string, int|string> $basketProducts
     * @param array<int, Product>|null $productsById
     */
    public function calculateBasketCost(array $basketProducts, ?array $productsById = null): float
    {
        $normalizedBasketProducts = $this->normalizeBasketProducts($basketProducts);
        if ([] === $normalizedBasketProducts) {
            return 0.0;
        }

        $productsById ??= $this->productRepository->findByIdsIndexed(array_keys($normalizedBasketProducts));

        $cost = 0.0;
        foreach ($normalizedBasketProducts as $productId => $amount) {
            $product = $productsById[$productId] ?? null;
            if (!$product instanceof Product) {
                continue;
            }

            $cost += (float) $product->getPrice() * $amount;
        }

        return $cost;
    }
    
    /**
     * @param array<int|string, int|string> $basketProducts
     *
     * @return array<int, int>
     */
    private function normalizeBasketProducts(array $basketProducts): array
    {
        $normalized = [];
        foreach ($basketProducts as $productId => $amount) {
            $normalizedProductId = (int) $productId;
            $normalizedAmount = (int) $amount;
            if ($normalizedProductId <= 0 || $normalizedAmount <= 0) {
                continue;
            }

            $normalized[$normalizedProductId] = $normalizedAmount;
        }
        ksort($normalized);

        return $normalized;
    }

    private function getCheckoutOwnerKey(string $addressId): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null !== $request && $request->hasSession()) {
            return 'session:' . $request->getSession()->getId();
        }

        return 'address:' . $addressId;
    }
    private function buildExistingOrderResult(Order $order): OrderCheckoutResult
    {
        if ($order->isPaymentFailed()) {
            return OrderCheckoutResult::failure('This checkout request already failed. Please retry.');
        }

        if ($order->isPaypalPayment() && $order->isPendingPayment()) {
            return OrderCheckoutResult::failure('Order already exists. Please finish payment in PayPal.');
        }

        $this->clearBasket->clear();

        return OrderCheckoutResult::success('Order is already being processed.');
    }

    private function dispatchAsyncFinalization(
        Order $order,
        PaymentGateway $payment,
        string $addressId,
        string $idempotencyKey
    ): OrderCheckoutResult {
        try {
            $this->commandBus->dispatch(new FinalizeOrderCommand((int) $order->getId()));
            $this->logger->info('Checkout finalization command dispatched to async transport.', [
                'orderId' => $order->getId(),
                'paymentMethod' => $payment->getPaymentName(),
                'idempotencyKey' => $idempotencyKey,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->warning('Async order finalization dispatch failed. Falling back to sync transport.', [
                'orderId' => $order->getId(),
                'addressId' => $addressId,
                'paymentMethod' => $payment->getPaymentName(),
                'idempotencyKey' => $idempotencyKey,
                'exceptionMessage' => $exception->getMessage(),
            ]);

            try {
                $this->commandBus->dispatch(
                    new FinalizeOrderCommand((int) $order->getId()),
                    [new TransportNamesStamp(['sync'])]
                );
            } catch (\Throwable $syncException) {
                try {
                    $this->markOrderAsPaymentFailedAndRestoreQuantities($order);
                } catch (\Throwable $markFailedException) {
                    $this->logger->error('Could not mark order as payment_failed after dispatch failure.', [
                        'orderId' => $order->getId(),
                        'exceptionMessage' => $markFailedException->getMessage(),
                    ]);
                }

                $this->logger->error('Could not dispatch order finalization command (async and sync fallback failed).', [
                    'orderId' => $order->getId(),
                    'addressId' => $addressId,
                    'paymentMethod' => $payment->getPaymentName(),
                    'idempotencyKey' => $idempotencyKey,
                    'asyncExceptionMessage' => $exception->getMessage(),
                    'syncExceptionMessage' => $syncException->getMessage(),
                ]);

                return OrderCheckoutResult::failure('Error finalizing order. Please contact support.');
            }
        }

        $this->clearBasket->clear();

        return OrderCheckoutResult::success('Order received. Payment is being processed.');
    }
}
