<?php

declare(strict_types=1);

namespace App\Basket\Infrastructure\Persistence\Doctrine;

use App\Basket\Application\Port\IProductReadRepository;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use Doctrine\ORM\EntityManagerInterface;
class BasketProductReadRepository implements IProductReadRepository
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }
    public function getProductById(int $productId): ?Product
    {
        return $this->entityManager->getRepository(Product::class)->find($productId);
    }
}
