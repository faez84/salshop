<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use App\Checkout\Application\Port\IOrderArticleSaver;
use RuntimeException;

class OrderArticleSaver implements IOrderArticleSaver
{
    public function __construct(
        readonly protected OrderProductRepository $orderProductRepository,
        readonly protected ProductRepository $productRepository
    )
    {
    }

    public function save(Order $order, array $productIds, ?array $productsById = null): void
    {
        $normalizedProducts = $this->normalizeBasketProducts($productIds);
        if ([] === $normalizedProducts) {
            return;
        }

        $productsById ??= $this->productRepository->findByIdsIndexed(array_keys($normalizedProducts));

        foreach ($normalizedProducts as $productId => $amount) {
            $product = $productsById[$productId] ?? null;
            if (!$product instanceof Product) {
                throw new RuntimeException(sprintf('Product with ID %s was not found.', (string) $productId));
            }

            $quantity = (int) $product->getQuantity();
            if ($amount > $quantity) {
                throw new RuntimeException(sprintf('Product: %s is out of stock.', $product->getTitle()));
            }

            $cost = (float) $product->getPrice() * $amount;
            $this->orderProductRepository->save(OrderProduct::create($amount, $cost, $order, $product), false);
            $this->productRepository->updateQuantity($product, $quantity - $amount, false);
        }
    }

    /**
     * @param array<int|string, int|string> $productIds
     *
     * @return array<int, int>
     */
    private function normalizeBasketProducts(array $productIds): array
    {
        $normalized = [];
        foreach ($productIds as $productId => $amount) {
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
}
