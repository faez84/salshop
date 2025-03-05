<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Order;
use Symfony\Contracts\EventDispatcher\Event;

class OrderEvent extends Event
{
    public const NAME = 'order.event';

    public function __construct(readonly private Order $order)
    {
    }

    public function getOrder(): Order
    {
        return $this->order;
    }
}
