<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Security\Core\User\UserInterface;

final class ApiRateLimitSubscriber implements EventSubscriberInterface
{
    private const LIMIT_ATTR = '_api_rate_limit';

    public function __construct(
        #[Autowire(service: 'limiter.api_request')]
        private readonly RateLimiterFactory $apiLimiter,
        private readonly Security $security
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 16],
            KernelEvents::RESPONSE => ['onKernelResponse', -64],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->shouldRateLimit($request)) {
            return;
        }

        $rateLimit = $this->apiLimiter->create($this->resolveRateLimitKey($request))->consume(1);
        $request->attributes->set(self::LIMIT_ATTR, $rateLimit);
        if ($rateLimit->isAccepted()) {
            return;
        }

        $headers = $this->buildRateLimitHeaders($rateLimit);
        $headers['Retry-After'] = (string) $this->calculateRetryAfterSeconds($rateLimit);
        $headers['Cache-Control'] = 'no-store';

        $event->setResponse(new JsonResponse([
            'status' => 'error',
            'error' => 'rate_limited',
            'message' => 'Too many requests. Please retry later.',
        ], Response::HTTP_TOO_MANY_REQUESTS, $headers));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$this->shouldRateLimit($request)) {
            return;
        }

        $rateLimit = $request->attributes->get(self::LIMIT_ATTR);
        if (!$rateLimit instanceof RateLimit) {
            return;
        }

        foreach ($this->buildRateLimitHeaders($rateLimit) as $header => $value) {
            $event->getResponse()->headers->set($header, $value);
        }
    }

    private function shouldRateLimit(Request $request): bool
    {
        $path = $request->getPathInfo();

        if (!str_starts_with($path, '/api')) {
            return false;
        }

        if (str_starts_with($path, '/api/docs')) {
            return false;
        }

        return true;
    }

    private function resolveRateLimitKey(Request $request): string
    {
        $user = $this->security->getUser();
        if ($user instanceof UserInterface) {
            $identifier = strtolower(trim($user->getUserIdentifier()));
            if ('' !== $identifier) {
                return 'user:' . $identifier;
            }
        }

        return 'ip:' . ($request->getClientIp() ?? 'unknown');
    }

    /**
     * @return array<string, string>
     */
    private function buildRateLimitHeaders(RateLimit $rateLimit): array
    {
        return [
            'X-RateLimit-Limit' => (string) $rateLimit->getLimit(),
            'X-RateLimit-Remaining' => (string) $rateLimit->getRemainingTokens(),
            'X-RateLimit-Reset' => $rateLimit->getRetryAfter()->format(\DateTimeInterface::RFC7231),
        ];
    }

    private function calculateRetryAfterSeconds(RateLimit $rateLimit): int
    {
        $delta = $rateLimit->getRetryAfter()->format('U.u') - microtime(true);

        return max(1, (int) ceil($delta));
    }
}
