<?php

declare(strict_types=1);

namespace App\Catalog\Application\Port\Persistence;

use App\Catalog\Infrastructure\Persistence\Doctrine\Product;


interface IProductRepository
{ 
    public function findByCategoryForList(int $categoryId): array;
    public function findDetailSummaryById(int $productId): ?array;
    public function findInValues(array $ids): array;
    public function findByIdsIndexed(array $ids): array;
    public function updateQuantity(Product $product, int $newQuantity, bool $flush = false): void;
}
