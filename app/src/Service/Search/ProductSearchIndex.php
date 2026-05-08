<?php

declare(strict_types=1);

namespace App\Service\Search;

final class ProductSearchIndex
{
    public const ALIAS_NAME = 'products';
    public const INDEX_PREFIX = 'products_v';

    public static function generateVersionedIndexName(?string $suffix = null): string
    {
        $rawSuffix = $suffix ?? (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Ymd_His');
        $normalizedSuffix = strtolower(trim((string) $rawSuffix));
        $normalizedSuffix = (string) preg_replace('/[^a-z0-9_-]+/', '-', $normalizedSuffix);
        $normalizedSuffix = trim($normalizedSuffix, '-_');

        if ($normalizedSuffix === '') {
            throw new \InvalidArgumentException('Index suffix cannot be empty after normalization.');
        }

        return sprintf('%s%s', self::INDEX_PREFIX, $normalizedSuffix);
    }

    /**
     * @return array<string, mixed>
     */
    public static function definition(string $indexName): array
    {
        return [
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'normalizer' => [
                            'lowercase_normalizer' => [
                                'type' => 'custom',
                                'filter' => ['lowercase'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'dynamic' => 'strict',
                    'properties' => [
                        'name' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                    'ignore_above' => 256,
                                ],
                            ],
                        ],
                        'artNumber' => [
                            'type' => 'keyword',
                            'normalizer' => 'lowercase_normalizer',
                        ],
                        'description' => ['type' => 'text'],
                        'features' => ['type' => 'text'],
                        'price' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                        'quantity' => ['type' => 'integer'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public static function documentFromRow(array $row): array
    {
        return [
            'name' => (string) ($row['title'] ?? ''),
            'artNumber' => (string) ($row['art_num'] ?? ''),
            'description' => (string) ($row['description'] ?? ''),
            'features' => (string) ($row['features'] ?? ''),
            'price' => (float) ($row['price'] ?? 0),
            'quantity' => (int) ($row['quantity'] ?? 0),
        ];
    }
}
