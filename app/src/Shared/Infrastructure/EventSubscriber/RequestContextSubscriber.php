<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

 
use App\Shared\Infrastructure\Observability\RequestContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

final class RequestContextSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestContext $requestContext
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 2048],
            KernelEvents::RESPONSE => ['onKernelResponse', -2048],
            KernelEvents::FINISH_REQUEST => ['onKernelFinishRequest', -2048],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = trim((string) $request->headers->get('X-Request-Id', ''));
        if ('' === $requestId) {
            $requestId = $this->generateRequestId();
        }

        $request->attributes->set('_request_id', $requestId);
        $this->requestContext->setFromRequest($request, $requestId);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $requestId = trim((string) $request->attributes->get('_request_id', ''));
        if ('' === $requestId) {
            $requestId = $this->generateRequestId();
            $request->attributes->set('_request_id', $requestId);
            $this->requestContext->setFromRequest($request, $requestId);
        }

        $event->getResponse()->headers->set('X-Request-Id', $requestId);
    }

    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->requestContext->clear();
        }
    }

    private function generateRequestId(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Throwable) {
            return uniqid('req-', true);
        }
    }
}
