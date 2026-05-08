<?php

declare(strict_types=1);

namespace App\Address\Application\Port\Persistence;

use App\Address\Infrastructure\Persistence\Doctrine\Address;

interface IAddressRepository
{
    public function getAddressById(int $addressId): ?Address;
}
