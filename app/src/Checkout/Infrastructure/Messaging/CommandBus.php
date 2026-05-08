<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging;

use App\Checkout\Application\Port\Messaging\ICommandBus;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class CommandBus implements ICommandBus
{
    public function __construct(
        #[Autowire(service: 'messenger.bus.command')]
        private readonly MessageBusInterface $bus
    ) {
    }

    /**
     * @param array<int, object> $stamps
     */
    public function dispatch(object $command, array $stamps = []): Envelope
    {
        return $this->bus->dispatch($command, $stamps);
    }
}
