<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final class WorkerMemoryCleanupSubscriber implements EventSubscriberInterface
{
    private int $processedMessages = 0;

    private const LOG_EVERY_MESSAGES = 200;
    private const HIGH_MEMORY_WATERMARK_BYTES = 220200960; // 210 MiB

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        #[Autowire(service: 'monolog.logger.checkout')]
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageHandledEvent::class => 'cleanupAfterMessage',
            WorkerMessageFailedEvent::class => 'cleanupAfterMessage',
        ];
    }

    public function cleanupAfterMessage(WorkerMessageHandledEvent|WorkerMessageFailedEvent $event): void
    {
        if (!$event->getEnvelope()->last(ReceivedStamp::class) instanceof ReceivedStamp) {
            return;
        }

        foreach ($this->managerRegistry->getManagers() as $manager) {
            $manager->clear();
        }

        gc_collect_cycles();
        $this->processedMessages++;

        if ($event instanceof WorkerMessageFailedEvent) {
            $this->logger->warning('Worker message failed.', [
                'messageClass' => $event->getEnvelope()->getMessage()::class,
                'exceptionClass' => $event->getThrowable()::class,
                'exceptionMessage' => $event->getThrowable()->getMessage(),
                'processedMessages' => $this->processedMessages,
                'memoryUsageMb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memoryPeakMb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
        }

        $currentMemoryUsage = memory_get_usage(true);
        if (($this->processedMessages % self::LOG_EVERY_MESSAGES) === 0 || $currentMemoryUsage >= self::HIGH_MEMORY_WATERMARK_BYTES) {
            $gcStatus = function_exists('gc_status') ? gc_status() : [];
            $this->logger->info('Worker runtime metrics.', [
                'processedMessages' => $this->processedMessages,
                'memoryUsageMb' => round($currentMemoryUsage / 1024 / 1024, 2),
                'memoryPeakMb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'gcRuns' => (int) ($gcStatus['runs'] ?? 0),
                'gcCollected' => (int) ($gcStatus['collected'] ?? 0),
                'gcThreshold' => (int) ($gcStatus['threshold'] ?? 0),
                'highWatermarkHit' => $currentMemoryUsage >= self::HIGH_MEMORY_WATERMARK_BYTES,
            ]);
        }
    }
}
