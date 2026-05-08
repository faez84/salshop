<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\HealthController;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

final class HealthControllerTest extends TestCase
{
    public function testLiveEndpointReturnsOk(): void
    {
        $controller = new HealthController(
            $this->createMock(EntityManagerInterface::class),
            new LockFactory(new InMemoryStore())
        );

        $response = $controller->live();
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray($payload);
        self::assertSame('ok', $payload['status']);
    }

    public function testReadyEndpointReturnsReadyWhenChecksPass(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT 1')
            ->willReturn(1);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $_ENV['MESSENGER_CHECKOUT_TRANSPORT_DSN'] = 'in-memory://';

        $controller = new HealthController($entityManager, new LockFactory(new InMemoryStore()));
        $response = $controller->ready();
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(200, $response->getStatusCode());
        self::assertIsArray($payload);
        self::assertSame('ready', $payload['status']);
        self::assertSame('up', $payload['checks']['database']);
        self::assertSame('up', $payload['checks']['lock_store']);
        self::assertSame('configured', $payload['checks']['checkout_transport']);
    }

    public function testReadyEndpointReturnsServiceUnavailableWhenDatabaseCheckFails(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects(self::once())
            ->method('fetchOne')
            ->with('SELECT 1')
            ->willThrowException(new \RuntimeException('DB unavailable'));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getConnection')
            ->willReturn($connection);

        $_ENV['MESSENGER_CHECKOUT_TRANSPORT_DSN'] = 'in-memory://';

        $controller = new HealthController($entityManager, new LockFactory(new InMemoryStore()));
        $response = $controller->ready();
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(503, $response->getStatusCode());
        self::assertIsArray($payload);
        self::assertSame('not_ready', $payload['status']);
        self::assertSame('down', $payload['checks']['database']);
    }
}
