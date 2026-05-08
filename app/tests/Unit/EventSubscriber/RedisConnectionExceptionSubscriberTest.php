<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Shared\Infrastructure\EventSubscriber\RedisConnectionExceptionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Twig\Error\RuntimeError;

final class RedisConnectionExceptionSubscriberTest extends TestCase
{
    public function testSubscriberReturns503ForRedisConnectionErrorOnWebRequest(): void
    {
        $subscriber = new RedisConnectionExceptionSubscriber();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/basket/payment', 'GET');
        $exception = new RuntimeError(
            'An exception has been thrown during the rendering of a template ("Connection refused [tcp://redis:6379]").',
            -1,
            null,
            new \RuntimeException('Connection refused [tcp://redis:6379]')
        );

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $subscriber->onKernelException($event);

        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        self::assertSame('5', $response->headers->get('Retry-After'));
        self::assertStringContainsString('Service temporarily unavailable', (string) $response->getContent());
    }

    public function testSubscriberReturnsJson503ForApiRequest(): void
    {
        $subscriber = new RedisConnectionExceptionSubscriber();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/products', 'GET');
        $exception = new \RuntimeException('Redis read error on connection to redis');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $subscriber->onKernelException($event);

        self::assertTrue($event->hasResponse());
        $response = $event->getResponse();
        self::assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertStringContainsString('"error":"redis_unavailable"', (string) $response->getContent());
    }

    public function testSubscriberDoesNotHandleNonRedisExceptions(): void
    {
        $subscriber = new RedisConnectionExceptionSubscriber();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/basket/payment', 'GET');
        $exception = new \RuntimeException('Database connection failed');

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $exception);
        $subscriber->onKernelException($event);

        self::assertFalse($event->hasResponse());
    }
}
