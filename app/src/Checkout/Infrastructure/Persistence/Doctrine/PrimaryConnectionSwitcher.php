<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use App\Checkout\Application\Port\Persistence\IPrimaryConnectionSwitcher;
use Doctrine\ORM\EntityManagerInterface;

class PrimaryConnectionSwitcher implements IPrimaryConnectionSwitcher
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
    public function forcePrimaryConnection(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection instanceof PrimaryReadReplicaConnection) {
            $connection->ensureConnectedToPrimary();
        }
    }
}