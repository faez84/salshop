<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Payment;

use App\Checkout\Application\Exceptions\CheckoutValidationException;
use App\Checkout\Application\Port\Persistence\IPromotionRepository;
use App\Checkout\Application\Port\Promotion\IPromotionManager;
use App\Checkout\Domain\ValueObject\PromotionCalculationResult;

use App\Checkout\Infrastructure\Persistence\Doctrine\Promotion;
use DateTimeImmutable;

final class PromotionManager implements IPromotionManager
{
    public function __construct(
        private readonly IPromotionRepository $promotionRepository
    ) {
    }

    public function resolveCheckoutAmounts(?string $promotionCode, float $subtotal): PromotionCalculationResult
    {
        if ($subtotal < 0) {
            throw new CheckoutValidationException('Basket subtotal cannot be negative.');
        }

        $normalizedCode = strtoupper(trim((string) $promotionCode));
        if ('' === $normalizedCode) {
            return PromotionCalculationResult::withoutPromotion($subtotal);
        }

        $promotion = $this->promotionRepository->findOneByCode($normalizedCode);
        if (!$promotion instanceof Promotion) {
            throw new CheckoutValidationException('Promotion code is invalid.');
        }

        $this->assertPromotionCanBeApplied($promotion, $subtotal, new DateTimeImmutable());

        $discountAmount = $this->calculateDiscountAmount($promotion, $subtotal);
        $finalCost = max(0.0, round($subtotal - $discountAmount, 2));

        return PromotionCalculationResult::withPromotion($finalCost, $discountAmount, $promotion);
    }

    public function markPromotionAsUsed(?Promotion $promotion): void
    {
        if (!$promotion instanceof Promotion) {
            return;
        }

        $promotion->setUsedCount($promotion->getUsedCount() + 1);
        $this->promotionRepository->save($promotion);
    }

    private function assertPromotionCanBeApplied(Promotion $promotion, float $subtotal, DateTimeImmutable $now): void
    {
        if (!$promotion->isActive()) {
            throw new CheckoutValidationException('Promotion code is inactive.');
        }

        $validFrom = $promotion->getValidFrom();
        if ($validFrom instanceof DateTimeImmutable && $now < $validFrom) {
            throw new CheckoutValidationException('Promotion code is not active yet.');
        }

        $validUntil = $promotion->getValidUntil();
        if ($validUntil instanceof DateTimeImmutable && $now > $validUntil) {
            throw new CheckoutValidationException('Promotion code has expired.');
        }

        $usageLimit = $promotion->getUsageLimit();
        if (null !== $usageLimit && $promotion->getUsedCount() >= $usageLimit) {
            throw new CheckoutValidationException('Promotion code usage limit has been reached.');
        }

        $minimumBasketCost = $promotion->getMinimumBasketCost();
        if (null !== $minimumBasketCost && $subtotal < $minimumBasketCost) {
            throw new CheckoutValidationException(
                sprintf('Promotion requires a basket total of at least %.2f.', $minimumBasketCost)
            );
        }
    }

    private function calculateDiscountAmount(Promotion $promotion, float $subtotal): float
    {
        $value = (float) $promotion->getValue();
        $type = (string) $promotion->getType();
        $discountAmount = match ($type) {
            Promotion::TYPE_FIXED => $value,
            Promotion::TYPE_PERCENTAGE => $subtotal * ($value / 100),
            default => throw new CheckoutValidationException('Promotion type is invalid.'),
        };

        if ($discountAmount <= 0) {
            throw new CheckoutValidationException('Promotion discount must be greater than zero.');
        }

        return round(min($discountAmount, $subtotal), 2);
    }
}
