<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Inventory;

interface IStockReservationManager
{
    /**
     * @param array<int|string, int|string> $basketProducts
     */
    public function reserveForCheckout(string $reservationKey, array $basketProducts): void;

    public function releaseForCheckout(string $reservationKey): void;
}
