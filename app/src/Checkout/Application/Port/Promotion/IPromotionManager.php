<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Promotion;

use App\Checkout\Domain\ValueObject\PromotionCalculationResult;
use App\Checkout\Infrastructure\Persistence\Doctrine\Promotion;

interface IPromotionManager
{
    public function resolveCheckoutAmounts(?string $promotionCode, float $subtotal): PromotionCalculationResult;

    public function markPromotionAsUsed(?Promotion $promotion): void;
}
