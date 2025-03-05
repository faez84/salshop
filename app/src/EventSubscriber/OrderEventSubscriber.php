<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\OrderEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            OrderEvent::NAME => 'onOrderFinished'
        ];
    }

    public function onOrderFinished(OrderEvent $orderEvent): void
    {
        $order = $orderEvent->getOrder();
        $order->setStatus('Finished');
        $this->em->persist($order);
        $this->em->flush();
    }
}
