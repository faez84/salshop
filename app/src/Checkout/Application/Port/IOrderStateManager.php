<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port;

use App\Checkout\Domain\Entity\Order;

interface IOrderStateManager
{
    public function markAsPaid(Order $order): bool;

    public function markAsPaymentFailed(Order $order, bool $restoreReservedProductQuantities = true): bool;

    public function markAsRefunded(Order $order): bool;

    public function markAsChargeback(Order $order): bool;
}
