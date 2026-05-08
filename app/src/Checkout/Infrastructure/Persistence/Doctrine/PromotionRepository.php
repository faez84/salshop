<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use App\Checkout\Application\Port\Persistence\IPromotionRepository;
use App\Checkout\Infrastructure\Persistence\Doctrine\Promotion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Promotion>
 */
class PromotionRepository extends ServiceEntityRepository implements IPromotionRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Promotion::class);
    }

    public function save(Promotion $promotion, bool $flush = false): Promotion
    {
        $this->getEntityManager()->persist($promotion);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $promotion;
    }

    public function findOneByCode(string $code): ?Promotion
    {
        return $this->findOneBy(['code' => strtoupper(trim($code))]);
    }
}
