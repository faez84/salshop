<?php

declare(strict_types=1);

namespace App\Checkout\Domain\EventSubscriber;

use App\Checkout\Domain\Event\OrderCreated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderCreated::class => 'onOrderFinished',
        ];
    }

    public function onOrderFinished(OrderCreated $orderCreated): void
    {
        $this->logger->info('Order finished event dispatched.', [
            'orderId' => $orderCreated->getOrderId(),
        ]);
    }
}
