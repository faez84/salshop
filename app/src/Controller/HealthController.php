<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

final class HealthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LockFactory $lockFactory
    ) {
    }

    #[Route(path: '/health/live', name: 'health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'service' => 'salshop-checkout',
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }

    #[Route(path: '/health/ready', name: 'health_ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'lock_store' => $this->checkLockStore(),
            'checkout_transport' => $this->checkCheckoutTransport(),
        ];

        $allReady = true;
        foreach ($checks as $status) {
            if ('up' !== $status && 'configured' !== $status) {
                $allReady = false;
                break;
            }
        }

        $statusCode = $allReady ? JsonResponse::HTTP_OK : JsonResponse::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => $allReady ? 'ready' : 'not_ready',
            'service' => 'salshop-checkout',
            'checks' => $checks,
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], $statusCode);
    }

    private function checkDatabase(): string
    {
        try {
            $result = $this->entityManager->getConnection()->fetchOne('SELECT 1');

            return ((string) $result === '1' || (int) $result === 1) ? 'up' : 'down';
        } catch (Throwable) {
            return 'down';
        }
    }

    private function checkLockStore(): string
    {
        try {
            $lock = $this->lockFactory->createLock('healthcheck:lock:' . bin2hex(random_bytes(4)), 2.0);
            $acquired = $lock->acquire();
            if (!$acquired) {
                return 'down';
            }

            $lock->release();

            return 'up';
        } catch (Throwable) {
            return 'down';
        }
    }

    private function checkCheckoutTransport(): string
    {
        $dsn = trim((string) ($_ENV['MESSENGER_CHECKOUT_TRANSPORT_DSN']
            ?? $_SERVER['MESSENGER_CHECKOUT_TRANSPORT_DSN']
            ?? ''));

        return '' === $dsn ? 'down' : 'configured';
    }
}
