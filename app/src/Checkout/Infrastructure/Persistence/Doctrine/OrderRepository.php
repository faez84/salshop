<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use App\Checkout\Application\Port\Persistence\IOrderRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository implements IOrderRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }
    public function save(Order $order, bool $flush = false): Order
    {
        $this->getEntityManager()->persist($order);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $order;
    }

    public function findById(int $orderId): ?Order
    {
        $order = $this->find($orderId);

        return $order instanceof Order ? $order : null;
    }

    public function findOneByIdempotencyKey(string $key): ?Order
    {
        return $this->findOneBy(['idempotencyKey' => $key]);
    }

    public function findOneByProviderOrderId(string $providerOrderId): ?Order
    {
        return $this->findOneBy(['providerOrderId' => $providerOrderId]);
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

    /**
     * Finds the number of orders created in the last three months grouped by day.
     *
     * @return array<int, object> Array containing order count and creation date per day
     */
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
