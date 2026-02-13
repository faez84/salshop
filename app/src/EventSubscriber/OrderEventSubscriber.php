<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\OrderEvent;
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
            OrderEvent::class => 'onOrderFinished',
        ];
    }

    public function onOrderFinished(OrderEvent $orderEvent): void
    {
        $this->logger->info('Order finished event dispatched.', [
            'orderId' => $orderEvent->getOrder()->getId(),
        ]);
    }
}
