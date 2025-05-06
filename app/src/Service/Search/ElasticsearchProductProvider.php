<?php

declare(strict_types=1);

namespace App\Service\Search;

use ApiPlatform\State\ProviderInterface;
use App\Entity\ProductElastic;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use ApiPlatform\Metadata\Operation;

/**
 * This class is based on API Platforms elasticsearch implemenation.
 * However, It uses the FOS Elastica Bundle connection.
 *
 * @see \ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\CollectionDataProvider
 * @see \ApiPlatform\Core\Bridge\Elasticsearch\DataProvider\ItemDataProvider
 */
class ElasticsearchProductProvider implements ProviderInterface
{
    public $client;
    public function __construct(
        private DenormalizerInterface $denormalizer
    ) {
        $this->client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $filters = $context['filters'] ?? [];
    // Get pagination parameters from context
    $page = $context['pagination']['page'] ?? 1;
    $itemsPerPage = $context['pagination']['itemsPerPage'] ?? 30;

    $from = ($page - 1) * $itemsPerPage;
        $must = [];
        $should = [];

        // Full-text search if `search` param is provided
        if (isset($filters['search'])) {
            $should[] = [
                'multi_match' => [
                    'query' => $filters['search'],
                    'from' => $from,
                    'size' => $itemsPerPage,
                    'fields' => [
                        'title^3',
                        'description',
                        'features',
                        'image',
                        'artNum',
                        'category',
                    ],
                    'type' => 'best_fields'
                ]
            ];
        }
    
        // Exact field filters
        foreach (['title', 'artNum', 'price', 'quantity', 'features'] as $field) {
            if (!empty($filters[$field])) {
                $must[] = [
                    'match' => [
                        $field => $filters[$field],
                    ]
                ];
            }
        }
    
        // Final query
        $query = [];
        if ($should) {
            $query['bool']['should'] = $should;
            $query['bool']['minimum_should_match'] = 1;
        }
        if ($must) {
            $query['bool']['must'] = array_merge($query['bool']['must'] ?? [], $must);
        }
        if (!$must && !$should) {
            $query = ['match_all' => (object)[]];
        }
    
        $response = $this->client->search([
            'index' => 'productelastic',
            'body' => ['query' => $query]
        ]);
    
        $items = [];
        foreach ($response['hits']['hits'] as $hit) {
            $items[] = $this->denormalizer->denormalize($hit['_source'], ProductElastic::class);
        }
    
        $total = $response['hits']['total']['value'] ?? 0;
    
        return new ElasticsearchPaginator($items, $page, $itemsPerPage, $total);
    }
}