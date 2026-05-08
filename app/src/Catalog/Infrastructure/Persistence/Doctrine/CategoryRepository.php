<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Persistence\Doctrine;

use App\Catalog\Application\Port\Persistence\ICategoryRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository implements ICategoryRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return array<int, array{id:int, title:string}>
     */
    public function findRootCategorySummaries(): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id AS id', 'c.title AS title')
            ->andWhere('c.parent IS NULL')
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
