<?php

declare(strict_types=1);

namespace App\Service\Search;

use Elastic\Elasticsearch\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:search:create-index',
    description: 'Creates the Elasticsearch index for products.',
)]
class CreateIndexCommand extends Command
{
    public function __construct(
        private readonly Client $elasticsearchClient,
        private readonly ProductSearchAliasManager $aliasManager
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'index-name',
            null,
            InputOption::VALUE_REQUIRED,
            'Target index name. If omitted, a versioned name will be generated.'
        );
        $this->addOption(
            'recreate',
            null,
            InputOption::VALUE_NONE,
            'Delete and recreate the index if it already exists.'
        );
        $this->addOption(
            'activate',
            null,
            InputOption::VALUE_NONE,
            sprintf(
                'Switch alias "%s" to the target index after creation.',
                ProductSearchIndex::ALIAS_NAME
            )
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $rawIndexName = trim((string) $input->getOption('index-name'));
            $indexName = $rawIndexName !== '' ? $rawIndexName : ProductSearchIndex::generateVersionedIndexName();
            $exists = $this->elasticsearchClient
                ->indices()
                ->exists(['index' => $indexName])
                ->asBool();

            if ($exists && !$input->getOption('recreate')) {
                $output->writeln(sprintf(
                    'Index "%s" already exists. Use --recreate to rebuild it.',
                    $indexName
                ));
            } elseif ($exists) {
                $this->elasticsearchClient->indices()->delete(['index' => $indexName]);
                $output->writeln(sprintf('Deleted existing index "%s".', $indexName));

                $exists = false;
            }

            if (!$exists) {
                $this->elasticsearchClient->indices()->create(ProductSearchIndex::definition($indexName));
                $output->writeln(sprintf('Index "%s" is ready.', $indexName));
            }

            if ($input->getOption('activate')) {
                $this->aliasManager->switchAliasToIndex(ProductSearchIndex::ALIAS_NAME, $indexName);
                $output->writeln(sprintf(
                    'Alias "%s" now points to "%s".',
                    ProductSearchIndex::ALIAS_NAME,
                    $indexName
                ));
            } else {
                $output->writeln(sprintf(
                    'Index created without alias switch. Activate later with: app:search:create-index --index-name=%s --activate',
                    $indexName
                ));
            }
        } catch (\Throwable $e) {
            $output->writeln('Error creating index: ' . $e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

}
