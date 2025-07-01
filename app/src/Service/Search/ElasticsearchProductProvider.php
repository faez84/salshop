<?php

declare(strict_types=1);

namespace App\Service\Search;

use ApiPlatform\State\ProviderInterface;
use App\Entity\ProductElastic;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;

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
        private DenormalizerInterface $denormalizer,
        private Pagination $pagination
    ) {
        $this->client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $filters = $context['filters'] ?? [];
    // Get pagination parameters from context
    $page = $this->pagination->getPage($context);
    $itemsPerPage = $this->pagination->getLimit($operation);

    $from = ($page - 1) * $itemsPerPage;
        $must = [];
        $should = [];

        // Full-text search if `search` param is provided
        if (isset($filters['search'])) {
            $should[] = [
                'multi_match' => [
                    'query' => $filters['search'],
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
            'body' => [
                'from' => $from,
                'size' => $itemsPerPage,
                'query' => $query
            ]
        ]);
        $items = array_map(fn ($hit) => $this->denormalizer->denormalize($hit['_source'], ProductElastic::class), $response['hits']['hits']);
        $total = $response['hits']['total']['value'] ?? count($items);
    
        return new ElasticsearchPaginator($items, $page, $itemsPerPage, $total);
    }
}