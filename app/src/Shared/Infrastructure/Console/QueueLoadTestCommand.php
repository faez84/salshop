<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Infrastructure\Messaging\LoadTest\QueueLoadTestMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

#[AsCommand(
    name: 'app:queue:load-test',
    description: 'Dispatches a high number of test messages to stress RabbitMQ/Messenger.',
)]
final class QueueLoadTestCommand extends Command
{
    public function __construct(
        #[Autowire(service: 'messenger.bus.command')]
        private readonly MessageBusInterface $bus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Number of messages to publish.', 1000)
            ->addOption('batch', null, InputOption::VALUE_REQUIRED, 'Progress print interval.', 200)
            ->addOption('transport', null, InputOption::VALUE_REQUIRED, 'Transport name (e.g. async, checkout_async).', 'checkout_async')
            ->addOption('publish-interval-ms', null, InputOption::VALUE_REQUIRED, 'Delay between each publish in milliseconds.', 0)
            ->addOption('duration-seconds', null, InputOption::VALUE_REQUIRED, 'Optional runtime limit in seconds (0 = disabled).', 0)
            ->addOption('delay-ms', null, InputOption::VALUE_REQUIRED, 'Consumer-side delay per message in milliseconds.', 0)
            ->addOption('fail-every', null, InputOption::VALUE_REQUIRED, 'Force every Nth message to fail (0 disables forced failure).', 0)
            ->addOption('fail-percent', null, InputOption::VALUE_REQUIRED, 'Random failure chance (0-100).', 0)
            ->addOption('run-id', null, InputOption::VALUE_REQUIRED, 'Custom run identifier. Default: generated timestamp id.', '');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = (int) $input->getOption('count');
        $batch = (int) $input->getOption('batch');
        $publishIntervalMs = (int) $input->getOption('publish-interval-ms');
        $durationSeconds = (int) $input->getOption('duration-seconds');
        $delayMs = (int) $input->getOption('delay-ms');
        $failEvery = (int) $input->getOption('fail-every');
        $failPercent = (int) $input->getOption('fail-percent');
        $transport = trim((string) $input->getOption('transport'));
        $runId = trim((string) $input->getOption('run-id'));

        if (
            ($count <= 0 && $durationSeconds <= 0)
            || $batch <= 0
            || $publishIntervalMs < 0
            || $durationSeconds < 0
            || $delayMs < 0
            || $failEvery < 0
            || $failPercent < 0
            || $failPercent > 100
            || $transport === ''
        ) {
            $output->writeln('Invalid options. Expected: (--count > 0 or --duration-seconds > 0), --batch > 0, --publish-interval-ms >= 0, --duration-seconds >= 0, --delay-ms >= 0, --fail-every >= 0, --fail-percent in [0,100], non-empty --transport.');

            return Command::FAILURE;
        }

        if ($runId === '') {
            $runId = 'load-' . date('YmdHis');
        }

        $output->writeln(sprintf(
            'Starting queue load test run="%s" count=%d durationSeconds=%d transport=%s publishIntervalMs=%d delayMs=%d failEvery=%d failPercent=%d',
            $runId,
            $count,
            $durationSeconds,
            $transport,
            $publishIntervalMs,
            $delayMs,
            $failEvery,
            $failPercent
        ));

        $startedAt = microtime(true);
        $deadline = $durationSeconds > 0 ? $startedAt + $durationSeconds : null;
        $published = 0;

        for ($i = 1; ; $i++) {
            if ($deadline !== null && microtime(true) >= $deadline) {
                break;
            }

            if ($count > 0 && $published >= $count) {
                break;
            }

            $message = new QueueLoadTestMessage($runId, $i, $delayMs, $failEvery, $failPercent);
            $this->bus->dispatch($message, [new TransportNamesStamp([$transport])]);
            $published++;

            if (($published % $batch) === 0) {
                if ($count > 0) {
                    $output->writeln(sprintf('Published %d/%d messages...', $published, $count));
                } else {
                    $output->writeln(sprintf('Published %d messages...', $published));
                }
            }

            if ($publishIntervalMs > 0) {
                usleep($publishIntervalMs * 1000);
            }
        }

        $elapsed = max(0.001, microtime(true) - $startedAt);
        $rate = $published / $elapsed;
        $output->writeln(sprintf(
            'Done. Published %d message(s) in %.2f sec (%.2f msg/sec).',
            $published,
            $elapsed,
            $rate
        ));

        return Command::SUCCESS;
    }
}
