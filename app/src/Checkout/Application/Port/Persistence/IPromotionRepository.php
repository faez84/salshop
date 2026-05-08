<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Persistence;

use App\Checkout\Infrastructure\Persistence\Doctrine\Promotion;


interface IPromotionRepository
{
    public function save(Promotion $promotion, bool $flush = false): Promotion;

    public function findOneByCode(string $code): ?Promotion;
}
