<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Persistence;

interface IPrimaryConnectionSwitcher
{
    public function forcePrimaryConnection(): void;
}