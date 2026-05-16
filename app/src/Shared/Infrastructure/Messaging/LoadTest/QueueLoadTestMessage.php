<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messaging\LoadTest;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class QueueLoadTestMessage
{
    public function __construct(
        private readonly string $runId,
        private readonly int $sequence,
        private readonly int $processingDelayMs = 0,
        private readonly int $failEvery = 0,
        private readonly int $failPercent = 0,
    ) {
    }

    public function getRunId(): string
    {
        return $this->runId;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function getProcessingDelayMs(): int
    {
        return $this->processingDelayMs;
    }

    public function getFailEvery(): int
    {
        return $this->failEvery;
    }

    public function getFailPercent(): int
    {
        return $this->failPercent;
    }
}
