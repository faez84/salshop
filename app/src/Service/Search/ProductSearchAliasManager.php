<?php

declare(strict_types=1);

namespace App\Service\Search;

use Elastic\Elasticsearch\Client;

final class ProductSearchAliasManager
{
    public function __construct(private readonly Client $elasticsearchClient)
    {
    }

    /**
     * @return array<int, string>
     */
    public function resolveAliasedIndices(string $alias): array
    {
        $aliasExists = $this->elasticsearchClient
            ->indices()
            ->existsAlias(['name' => $alias])
            ->asBool();

        if (!$aliasExists) {
            return [];
        }

        $aliasData = $this->elasticsearchClient
            ->indices()
            ->getAlias(['name' => $alias])
            ->asArray();

        return array_keys($aliasData);
    }

    public function switchAliasToIndex(string $alias, string $targetIndex): void
    {
        $actions = [];

        foreach ($this->resolveAliasedIndices($alias) as $currentIndex) {
            $actions[] = [
                'remove' => [
                    'index' => $currentIndex,
                    'alias' => $alias,
                ],
            ];
        }

        $actions[] = [
            'add' => [
                'index' => $targetIndex,
                'alias' => $alias,
                'is_write_index' => true,
            ],
        ];

        $this->elasticsearchClient->indices()->updateAliases([
            'body' => [
                'actions' => $actions,
            ],
        ]);
    }
}
