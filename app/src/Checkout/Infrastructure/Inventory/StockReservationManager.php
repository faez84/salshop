<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Inventory;

use App\Catalog\Application\Port\Persistence\IProductRepository;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use App\Checkout\Application\Exceptions\CheckoutValidationException;
use App\Checkout\Application\Port\Inventory\IStockReservationManager;
 
 
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

final class StockReservationManager implements IStockReservationManager
{
    private const RESERVATION_TTL_SECONDS = 600;

    public function __construct(
        private readonly IProductRepository$productRepository,
        #[Autowire(service: 'cache.app')]
        private readonly CacheItemPoolInterface $cache,
        private readonly LockFactory $lockFactory
    ) {
    }
    

    /**
     * @param array<int|string, int|string> $basketProducts
     */
    public function reserveForCheckout(string $reservationKey, array $basketProducts): void
    {
        $normalizedProducts = $this->normalizeBasketProducts($basketProducts);
        if ([] === $normalizedProducts) {
            throw new CheckoutValidationException('Your basket is empty.');
        }

        $productIds = array_keys($normalizedProducts);
        $locks = $this->acquireProductLocks($productIds);

        try {
            $productsById = $this->productRepository->findByIdsIndexed($productIds);
            $reservedTotals = [];
            foreach ($normalizedProducts as $productId => $requestedAmount) {
                $product = $productsById[$productId] ?? null;
                if (!$product instanceof Product) {
                    throw new CheckoutValidationException(sprintf('Product with ID %d was not found.', $productId));
                }

                $reservedQuantity = $this->readReservedQuantity($productId);
                $availableQuantity = (int) $product->getQuantity() - $reservedQuantity;
                if ($availableQuantity < $requestedAmount) {
                    throw new CheckoutValidationException(sprintf('Product "%s" is temporarily unavailable in requested quantity.', (string) $product->getTitle()));
                }

                $reservedTotals[$productId] = $reservedQuantity + $requestedAmount;
            }

            foreach ($reservedTotals as $productId => $newReservedQuantity) {
                $this->writeReservedQuantity($productId, $newReservedQuantity);
            }

            $checkoutReservationItem = $this->cache->getItem($this->checkoutReservationCacheKey($reservationKey));
            $checkoutReservationItem->set($normalizedProducts);
            $checkoutReservationItem->expiresAfter(self::RESERVATION_TTL_SECONDS);
            $this->cache->save($checkoutReservationItem);
        } finally {
            $this->releaseLocks($locks);
        }
    }

    public function releaseForCheckout(string $reservationKey): void
    {
        $checkoutReservationCacheKey = $this->checkoutReservationCacheKey($reservationKey);
        $checkoutReservationItem = $this->cache->getItem($checkoutReservationCacheKey);
        if (!$checkoutReservationItem->isHit()) {
            return;
        }

        $reservationPayload = $checkoutReservationItem->get();
        if (!is_array($reservationPayload)) {
            $this->cache->deleteItem($checkoutReservationCacheKey);

            return;
        }

        $reservedProducts = $this->normalizeBasketProducts($reservationPayload);
        if ([] === $reservedProducts) {
            $this->cache->deleteItem($checkoutReservationCacheKey);

            return;
        }

        $locks = $this->acquireProductLocks(array_keys($reservedProducts));
        try {
            foreach ($reservedProducts as $productId => $reservedAmount) {
                $currentReservedQuantity = $this->readReservedQuantity($productId);
                $newReservedQuantity = max(0, $currentReservedQuantity - $reservedAmount);
                $this->writeReservedQuantity($productId, $newReservedQuantity);
            }
            $this->cache->deleteItem($checkoutReservationCacheKey);
        } finally {
            $this->releaseLocks($locks);
        }
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

    /**
     * @param array<int, int> $productIds
     *
     * @return array<int, LockInterface>
     */
    private function acquireProductLocks(array $productIds): array
    {
        $locks = [];
        foreach ($productIds as $productId) {
            $lock = $this->lockFactory->createLock(sprintf('stock-reservation:%d', $productId), 5.0);
            if (!$lock->acquire()) {
                $this->releaseLocks($locks);
                throw new CheckoutValidationException('Stock reservation is currently busy. Please retry in a moment.');
            }

            $locks[] = $lock;
        }

        return $locks;
    }

    /**
     * @param array<int, LockInterface> $locks
     */
    private function releaseLocks(array $locks): void
    {
        foreach ($locks as $lock) {
            $lock->release();
        }
    }

    private function readReservedQuantity(int $productId): int
    {
        $item = $this->cache->getItem($this->productReservationCacheKey($productId));
        if (!$item->isHit()) {
            return 0;
        }

        $value = $item->get();
        if (!is_numeric($value)) {
            return 0;
        }

        return max(0, (int) $value);
    }

    private function writeReservedQuantity(int $productId, int $quantity): void
    {
        $cacheKey = $this->productReservationCacheKey($productId);
        if ($quantity <= 0) {
            $this->cache->deleteItem($cacheKey);

            return;
        }

        $item = $this->cache->getItem($cacheKey);
        $item->set($quantity);
        $item->expiresAfter(self::RESERVATION_TTL_SECONDS);
        $this->cache->save($item);
    }

    private function productReservationCacheKey(int $productId): string
    {
        return sprintf('stock_reservation_product_%d', $productId);
    }

    private function checkoutReservationCacheKey(string $reservationKey): string
    {
        return 'stock_reservation_checkout_' . md5($reservationKey);
    }
}
