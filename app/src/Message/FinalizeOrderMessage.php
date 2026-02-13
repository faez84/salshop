<?php

declare(strict_types=1);

namespace App\Message;

final class FinalizeOrderMessage
{
    public function __construct(private readonly int $orderId)
    {
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
