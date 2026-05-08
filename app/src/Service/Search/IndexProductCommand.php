<?php

declare(strict_types=1);

namespace App\Service\Search;

use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:search:index-product',
    description: 'Indexes products into Elasticsearch.',
)]
class IndexProductCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Client $elasticsearchClient,
        private readonly ProductSearchAliasManager $aliasManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'batch-size',
            null,
            InputOption::VALUE_REQUIRED,
            'How many documents to send per bulk request.',
            500
        );
        $this->addOption(
            'index',
            null,
            InputOption::VALUE_REQUIRED,
            sprintf(
                'Target index or alias. Default is alias "%s".',
                ProductSearchIndex::ALIAS_NAME
            ),
            ProductSearchIndex::ALIAS_NAME
        );
        $this->addOption(
            'refresh',
            null,
            InputOption::VALUE_NONE,
            'Refresh the index after indexing to make documents searchable immediately.'
        );
        $this->addOption(
            'swap-alias',
            null,
            InputOption::VALUE_NONE,
            sprintf(
                'After successful indexing, switch alias "%s" to --index (must be a concrete index).',
                ProductSearchIndex::ALIAS_NAME
            )
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batchSize = (int) $input->getOption('batch-size');
        if ($batchSize <= 0) {
            $output->writeln('Error indexing products: --batch-size must be greater than zero.');

            return Command::FAILURE;
        }

        $target = trim((string) $input->getOption('index'));
        if ($target === '') {
            $output->writeln('Error indexing products: --index cannot be empty.');

            return Command::FAILURE;
        }

        if (!$this->targetExists($target)) {
            $output->writeln(sprintf(
                'Target "%s" does not exist as an index or alias. Create index first.',
                $target
            ));

            return Command::FAILURE;
        }

        $connection = $this->entityManager->getConnection();
        $result = $connection->createQueryBuilder()
            ->select('p.id', 'p.title', 'p.art_num', 'p.description', 'p.price', 'p.quantity', 'p.features')
            ->from('product', 'p')
            ->orderBy('p.id', 'ASC')
            ->executeQuery();

        $operations = [];
        $indexedCount = 0;

        try {
            while (($row = $result->fetchAssociative()) !== false) {
                $operations[] = [
                    'index' => [
                        '_index' => $target,
                        '_id' => (string) $row['id'],
                    ],
                ];
                $operations[] = ProductSearchIndex::documentFromRow($row);
                $indexedCount++;

                if (($indexedCount % $batchSize) === 0) {
                    $this->flushBulk($operations);
                    $output->writeln(sprintf('Indexed %d products...', $indexedCount));
                    $operations = [];
                }
            }

            if ($operations !== []) {
                $this->flushBulk($operations);
            }

            if ($input->getOption('refresh')) {
                $this->elasticsearchClient->indices()->refresh(['index' => $target]);
            }

            if ($input->getOption('swap-alias')) {
                if ($target === ProductSearchIndex::ALIAS_NAME) {
                    throw new \RuntimeException(sprintf(
                        '--swap-alias requires a concrete index name, not alias "%s".',
                        ProductSearchIndex::ALIAS_NAME
                    ));
                }

                $isConcreteIndex = $this->elasticsearchClient
                    ->indices()
                    ->exists(['index' => $target])
                    ->asBool();

                if (!$isConcreteIndex) {
                    throw new \RuntimeException(sprintf(
                        'Cannot switch alias to "%s" because it is not a concrete index.',
                        $target
                    ));
                }

                $this->aliasManager->switchAliasToIndex(ProductSearchIndex::ALIAS_NAME, $target);
                $output->writeln(sprintf(
                    'Alias "%s" was switched to "%s".',
                    ProductSearchIndex::ALIAS_NAME,
                    $target
                ));
            }
        } catch (\Throwable $e) {
            $output->writeln('Error indexing products: ' . $e->getMessage());

            return Command::FAILURE;
        }

        $output->writeln(sprintf(
            'Indexed %d product(s) successfully into "%s".',
            $indexedCount,
            $target
        ));
        return Command::SUCCESS;
    }

    private function targetExists(string $target): bool
    {
        $indexExists = $this->elasticsearchClient
            ->indices()
            ->exists(['index' => $target])
            ->asBool();

        if ($indexExists) {
            return true;
        }

        return $this->elasticsearchClient
            ->indices()
            ->existsAlias(['name' => $target])
            ->asBool();
    }

    /**
     * @param array<int, array<string, mixed>> $operations
     */
    private function flushBulk(array $operations): void
    {
        $bulkResponse = $this->elasticsearchClient->bulk(['body' => $operations])->asArray();

        if (($bulkResponse['errors'] ?? false) !== true) {
            return;
        }

        foreach ($bulkResponse['items'] ?? [] as $item) {
            $indexResult = $item['index'] ?? null;
            if (!is_array($indexResult) || !isset($indexResult['error'])) {
                continue;
            }

            $error = is_array($indexResult['error'])
                ? json_encode($indexResult['error'], JSON_THROW_ON_ERROR)
                : (string) $indexResult['error'];

            throw new \RuntimeException(sprintf(
                'Elasticsearch bulk indexing failed for id %s: %s',
                (string) ($indexResult['_id'] ?? 'unknown'),
                $error
            ));
        }

        throw new \RuntimeException('Elasticsearch bulk request returned errors without detailed items.');
    }
}
