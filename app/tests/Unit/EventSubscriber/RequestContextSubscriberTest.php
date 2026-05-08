<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Shared\Infrastructure\EventSubscriber\RequestContextSubscriber;
use App\Shared\Infrastructure\Observability\RequestContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RequestContextSubscriberTest extends TestCase
{
    public function testSubscriberGeneratesAndPropagatesRequestId(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/basket/payment', 'GET');
        $request->attributes->set('_route', 'basket_payment');

        $context = new RequestContext();
        $subscriber = new RequestContextSubscriber($context);

        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($requestEvent);

        $requestId = (string) $request->attributes->get('_request_id');
        self::assertNotSame('', $requestId);
        self::assertSame($requestId, ($context->toArray())['request_id']);

        $response = new Response();
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $subscriber->onKernelResponse($responseEvent);
        self::assertSame($requestId, $response->headers->get('X-Request-Id'));
    }

    public function testSubscriberKeepsIncomingRequestIdHeader(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/health/live', 'GET');
        $request->headers->set('X-Request-Id', 'incoming-123');

        $context = new RequestContext();
        $subscriber = new RequestContextSubscriber($context);

        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelRequest($requestEvent);

        self::assertSame('incoming-123', $request->attributes->get('_request_id'));

        $finishEvent = new FinishRequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $subscriber->onKernelFinishRequest($finishEvent);
        self::assertSame([], $context->toArray());
    }
}
