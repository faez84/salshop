<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Application\Port\Persistence\IProductRepository;
 
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository implements IProductRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return array<int, array{id:int, title:string, image:?string, price:float}>
     */
    public function findByCategoryForList(int $categoryId): array
    {
        return $this->createQueryBuilder('p')
            ->select('p.id AS id', 'p.title AS title', 'p.image AS image', 'p.price AS price')
            ->andWhere('p.category = :categoryId')
            ->setParameter('categoryId', $categoryId)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array{id:int, title:string, image:?string, artNum:string, price:float, description:?string, quantity:int, features:?string}|null
     */
    public function findDetailSummaryById(int $productId): ?array
    {
        $result = $this->createQueryBuilder('p')
            ->select(
                'p.id AS id',
                'p.title AS title',
                'p.image AS image',
                'p.artNum AS artNum',
                'p.price AS price',
                'p.description AS description',
                'p.quantity AS quantity',
                'p.features AS features'
            )
            ->andWhere('p.id = :productId')
            ->setParameter('productId', $productId)
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult();

        return $result[0] ?? null;
    }


    /**
     * @param array<int|string> $ids
     * @return array<int, Product>
     */
    public function findInValues(array $ids): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.id in (:ids)')
            ->setParameter('ids', value: $ids)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<int|string, int|string> $ids
     *
     * @return array<int, Product>
     */
    public function findByIdsIndexed(array $ids): array
    {
        $normalizedIds = [];
        foreach ($ids as $id) {
            $normalizedId = (int) $id;
            if ($normalizedId > 0) {
                $normalizedIds[$normalizedId] = $normalizedId;
            }
        }

        if ([] === $normalizedIds) {
            return [];
        }

        /** @var array<int, Product> $products */
        $products = $this->createQueryBuilder('p')
            ->andWhere('p.id IN (:ids)')
            ->setParameter('ids', array_values($normalizedIds))
            ->getQuery()
            ->getResult();

        $indexedProducts = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            if (null === $productId) {
                continue;
            }

            $indexedProducts[$productId] = $product;
        }

        return $indexedProducts;
    }
    public function updateQuantity(Product $product, int $newQuantity, bool $flush = false): void
    {
        $product->setQuantity($newQuantity);
        $this->getEntityManager()->persist($product);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
