<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Messaging;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final class QueryBus
{
    use HandleTrait;

    public function __construct(
        #[Autowire(service: 'messenger.bus.query')]
        MessageBusInterface $bus
    ) {
        $this->messageBus = $bus;
    }

    public function askNullable(object $query): mixed
    {
        return $this->handle($query);
    }
}
