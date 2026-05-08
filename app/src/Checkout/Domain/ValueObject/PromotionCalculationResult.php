<?php

declare(strict_types=1);

namespace App\Checkout\Domain\ValueObject;

use App\Checkout\Infrastructure\Persistence\Doctrine\Promotion;

final class PromotionCalculationResult
{
    public function __construct(
        private readonly float $finalCost = 0.0,
        private readonly float $discountAmount = 0.0,
        private readonly ?string $promotionCode = null,
        private readonly ?Promotion $promotion = null
    ) {
    }

    public static function withoutPromotion(float $subtotal): self
    {
        return new self($subtotal, 0.0, null, null);
    }

    public static function withPromotion(float $finalCost, float $discountAmount, Promotion $promotion): self
    {
        return new self($finalCost, $discountAmount, $promotion->getCode(), $promotion);
    }

    public function getFinalCost(): float
    {
        return $this->finalCost;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function getPromotionCode(): ?string
    {
        return $this->promotionCode;
    }

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }
}
