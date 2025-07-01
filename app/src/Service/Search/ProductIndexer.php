<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Entity\Product;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

class ProductIndexer
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();
    }

    public function index(Product $product): void
    {
        $this->client->index([
            'index' => 'productelastic',
            'id'    => $product->getId(),
            'body'  => [
                'id' => $product->getId(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice(),
                'quantity' => $product->getQuantity(),
                'description' => $product->getDescription(),
                'image' => $product->getImage(),
                'artNum' => $product->getArtNum(),
                'features' => $product->getFeatures(),
                'category' => '/api/categories/' . $product->getCategory()?->getId(),
            ],
        ]);
    }

    public function delete(int $productId): void
    {
        $this->client->delete([
            'index' => 'productelastic',
            'id'    => $productId,
        ]);
    }
}