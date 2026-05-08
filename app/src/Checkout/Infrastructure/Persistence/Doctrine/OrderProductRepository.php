<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use App\Checkout\Application\Port\Persistence\IOrderProductRepository;
 
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderProduct>
 */
class OrderProductRepository extends ServiceEntityRepository implements IOrderProductRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderProduct::class);
    }
    public function save(OrderProduct $orderProduct, bool $flush = false): void 
    {

        $this->getEntityManager()->persist($orderProduct);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
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
