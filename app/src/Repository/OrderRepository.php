<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

       public function findOrderInLastThreeMonths(): array
       {
           return $this->createQueryBuilder('o')
                ->select("count(o.id) as orderCount, DATE_FORMAT(o.createdAt, '%Y-%m-%d') as dateAsDay")
               ->andWhere('o.createdAt > :createdAt')
               ->groupBy('dateAsDay')
               ->setParameter('createdAt', new \DateTime('-90 days'))
               ->getQuery()
               ->getArrayResult();
       }
}
