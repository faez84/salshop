<?php

declare(strict_types=1);

namespace App\Checkout\Application\Port\Messaging;

use Symfony\Component\Messenger\Envelope;

interface ICommandBus
{

    public function dispatch(object $command, array $stamps = []): Envelope;
}
