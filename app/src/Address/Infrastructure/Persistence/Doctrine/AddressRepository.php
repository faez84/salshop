<?php

declare(strict_types=1);

namespace App\Address\Infrastructure\Persistence\Doctrine;

use App\Address\Application\Port\Persistence\IAddressRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository implements IAddressRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }
    public function getAddressById(int $addressId): ?Address
    {
        return $this->find($addressId);
    }
}
