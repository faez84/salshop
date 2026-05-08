<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Promotion;

use App\Entity\Promotion;
use App\Exceptions\CheckoutValidationException;
use App\Repository\PromotionRepository;
use App\Service\Promotion\PromotionManager;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PromotionManagerTest extends TestCase
{
    public function testResolveCheckoutAmountsWithoutCodeReturnsSubtotalUnchanged(): void
    {
        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository->expects(self::never())->method('findOneByCode');

        $manager = new PromotionManager($promotionRepository);
        $result = $manager->resolveCheckoutAmounts('', 120.0);

        self::assertSame(120.0, $result->getFinalCost());
        self::assertSame(0.0, $result->getDiscountAmount());
        self::assertNull($result->getPromotion());
        self::assertNull($result->getPromotionCode());
    }

    public function testResolveCheckoutAmountsWithInvalidCodeThrowsException(): void
    {
        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository
            ->expects(self::once())
            ->method('findOneByCode')
            ->with('SAVE10')
            ->willReturn(null);

        $manager = new PromotionManager($promotionRepository);

        $this->expectException(CheckoutValidationException::class);
        $this->expectExceptionMessage('Promotion code is invalid.');

        $manager->resolveCheckoutAmounts('save10', 120.0);
    }

    public function testResolveCheckoutAmountsAppliesFixedDiscountWithCap(): void
    {
        $promotion = $this->createPromotion(Promotion::TYPE_FIXED, 200.0);
        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository->method('findOneByCode')->willReturn($promotion);

        $manager = new PromotionManager($promotionRepository);
        $result = $manager->resolveCheckoutAmounts('bigfixed', 90.0);

        self::assertSame(0.0, $result->getFinalCost());
        self::assertSame(90.0, $result->getDiscountAmount());
        self::assertSame('BIGFIXED', $result->getPromotionCode());
    }

    public function testResolveCheckoutAmountsAppliesPercentageDiscount(): void
    {
        $promotion = $this->createPromotion(Promotion::TYPE_PERCENTAGE, 10.0);
        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository->method('findOneByCode')->willReturn($promotion);

        $manager = new PromotionManager($promotionRepository);
        $result = $manager->resolveCheckoutAmounts('save10', 200.0);

        self::assertSame(180.0, $result->getFinalCost());
        self::assertSame(20.0, $result->getDiscountAmount());
    }

    public function testResolveCheckoutAmountsRejectsReachedUsageLimit(): void
    {
        $promotion = $this->createPromotion(Promotion::TYPE_PERCENTAGE, 10.0)
            ->setUsageLimit(1)
            ->setUsedCount(1);

        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository->method('findOneByCode')->willReturn($promotion);

        $manager = new PromotionManager($promotionRepository);

        $this->expectException(CheckoutValidationException::class);
        $this->expectExceptionMessage('Promotion code usage limit has been reached.');

        $manager->resolveCheckoutAmounts('save10', 100.0);
    }

    public function testResolveCheckoutAmountsRejectsBelowMinimumBasketCost(): void
    {
        $promotion = $this->createPromotion(Promotion::TYPE_FIXED, 10.0)
            ->setMinimumBasketCost(120.0);

        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository->method('findOneByCode')->willReturn($promotion);

        $manager = new PromotionManager($promotionRepository);

        $this->expectException(CheckoutValidationException::class);
        $this->expectExceptionMessage('Promotion requires a basket total of at least 120.00.');

        $manager->resolveCheckoutAmounts('save10', 100.0);
    }

    public function testMarkPromotionAsUsedPersistsIncrementedCounter(): void
    {
        $promotion = $this->createPromotion(Promotion::TYPE_FIXED, 10.0)->setUsedCount(4);

        $promotionRepository = $this->createMock(PromotionRepository::class);
        $promotionRepository
            ->expects(self::once())
            ->method('save')
            ->with($promotion, false)
            ->willReturn($promotion);

        $manager = new PromotionManager($promotionRepository);
        $manager->markPromotionAsUsed($promotion);

        self::assertSame(5, $promotion->getUsedCount());
    }

    private function createPromotion(string $type, float $value): Promotion
    {
        return (new Promotion())
            ->setCode('BIGFIXED')
            ->setType($type)
            ->setValue($value)
            ->setActive(true)
            ->setValidFrom((new DateTimeImmutable())->modify('-1 hour'))
            ->setValidUntil((new DateTimeImmutable())->modify('+1 hour'));
    }
}
