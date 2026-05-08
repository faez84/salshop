<?php

declare(strict_types=1);

namespace App\Service\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use InvalidArgumentException;
use RuntimeException;

final class ElasticsearchClientFactory
{
    public function create(string $hosts): Client
    {
        $hostList = array_values(array_filter(array_map('trim', explode(',', $hosts))));

        if ($hostList === []) {
            throw new InvalidArgumentException('ELASTICSEARCH_HOSTS cannot be empty.');
        }

        if (!class_exists(ClientBuilder::class)) {
            throw new RuntimeException(
                'Elasticsearch client classes are missing. Run composer install in app/ to install dependencies.'
            );
        }

        return ClientBuilder::create()
            ->setHosts($hostList)
            ->build();
    }
}
