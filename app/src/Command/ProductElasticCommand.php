<?php

// src/Command/IndexBooksCommand.php

namespace App\Command;


use App\Entity\Product;

use Doctrine\ORM\EntityManagerInterface;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:index-books')]
class ProductElasticCommand extends Command
{
    public Client $client;
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->client = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $products = $this->em->getRepository(Product::class)->findAll();

        foreach ($products as $product) {
            $this->client->index([
                'index' => 'productelastic',
                'id' => $product->getId(),
                'body' => [
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

        $output->writeln('Books indexed successfully.');
        return Command::SUCCESS;
    }
}
