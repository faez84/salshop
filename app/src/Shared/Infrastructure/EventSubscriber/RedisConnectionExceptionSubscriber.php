<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

final class RedisConnectionExceptionSubscriber implements EventSubscriberInterface
{
    private const RETRY_AFTER_SECONDS = '5';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 128],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->isRedisConnectionFailure($event->getThrowable())) {
            return;
        }

        $request = $event->getRequest();
        $message = 'Service temporarily unavailable. Please retry shortly.';
        $headers = ['Retry-After' => self::RETRY_AFTER_SECONDS];

        if ($request->getRequestFormat() === 'json' || str_starts_with($request->getPathInfo(), '/api')) {
            $event->setResponse(new JsonResponse([
                'status' => 'error',
                'error' => 'redis_unavailable',
                'message' => $message,
            ], Response::HTTP_SERVICE_UNAVAILABLE, $headers));

            return;
        }

        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        $event->setResponse(new Response($message, Response::HTTP_SERVICE_UNAVAILABLE, $headers));
    }

    private function isRedisConnectionFailure(Throwable $throwable): bool
    {
        $current = $throwable;
        while (null !== $current) {
            if ($this->isRedisConnectionFailureMessage($current->getMessage())) {
                return true;
            }

            $current = $current->getPrevious();
        }

        return false;
    }

    private function isRedisConnectionFailureMessage(string $message): bool
    {
        $normalized = strtolower(trim($message));
        if ('' === $normalized) {
            return false;
        }

        $mentionsRedis = str_contains($normalized, 'redis')
            || str_contains($normalized, 'tcp://redis:')
            || str_contains($normalized, 'predis')
            || str_contains($normalized, 'phpredis');

        if (!$mentionsRedis) {
            return false;
        }

        return str_contains($normalized, 'connection refused')
            || str_contains($normalized, 'connection timed out')
            || str_contains($normalized, 'read error on connection')
            || str_contains($normalized, 'connection reset');
    }
}
