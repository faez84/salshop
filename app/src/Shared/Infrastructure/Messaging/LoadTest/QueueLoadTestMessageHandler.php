<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\LoadTest;

use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final class QueueLoadTestMessageHandler
{
    public function __invoke(QueueLoadTestMessage $message): void
    {
        $delayMs = $message->getProcessingDelayMs();
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $failEvery = $message->getFailEvery();
        if ($failEvery > 0 && ($message->getSequence() % $failEvery) === 0) {
            throw new RuntimeException(sprintf(
                'Load test forced failure at sequence %d for run %s.',
                $message->getSequence(),
                $message->getRunId()
            ));
        }

        $failPercent = $message->getFailPercent();
        if ($failPercent > 0 && random_int(1, 100) <= $failPercent) {
            throw new RuntimeException(sprintf(
                'Load test probabilistic failure at sequence %d for run %s (failPercent=%d).',
                $message->getSequence(),
                $message->getRunId(),
                $failPercent
            ));
        }
    }
}
