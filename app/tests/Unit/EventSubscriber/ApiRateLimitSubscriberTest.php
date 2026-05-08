<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Shared\Infrastructure\EventSubscriber\ApiRateLimitSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class ApiRateLimitSubscriberTest extends TestCase
{
    public function testSubscriberIgnoresNonApiRoutes(): void
    {
        $subscriber = $this->createSubscriber(limit: 1);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/products', 'GET');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testSubscriberIgnoresApiDocsRoute(): void
    {
        $subscriber = $this->createSubscriber(limit: 1);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/docs', 'GET');

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($event);

        self::assertFalse($event->hasResponse());
    }

    public function testSubscriberAddsRateLimitHeadersForAcceptedApiRequest(): void
    {
        $subscriber = $this->createSubscriber(limit: 5);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/products', 'GET');

        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($requestEvent);
        self::assertFalse($requestEvent->hasResponse());

        $response = new Response('ok');
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber->onKernelResponse($responseEvent);

        self::assertSame('5', $response->headers->get('X-RateLimit-Limit'));
        self::assertNotSame('', (string) $response->headers->get('X-RateLimit-Remaining'));
        self::assertNotSame('', (string) $response->headers->get('X-RateLimit-Reset'));
    }

    public function testSubscriberReturns429WhenApiRateLimitIsExceeded(): void
    {
        $subscriber = $this->createSubscriber(limit: 1);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/products', 'GET');

        $first = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($first);
        self::assertFalse($first->hasResponse());

        $second = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($second);

        self::assertTrue($second->hasResponse());
        $response = $second->getResponse();
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertNotSame('', (string) $response->headers->get('Retry-After'));
        self::assertStringContainsString('"error":"rate_limited"', (string) $response->getContent());
    }

    private function createSubscriber(int $limit): ApiRateLimitSubscriber
    {
        $factory = new RateLimiterFactory([
            'id' => 'api_request_test_' . uniqid('', true),
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => '1 minute',
        ], new InMemoryStorage());

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        return new ApiRateLimitSubscriber($factory, $security);
    }
}
