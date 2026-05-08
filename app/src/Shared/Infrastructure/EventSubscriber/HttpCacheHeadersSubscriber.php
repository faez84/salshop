<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class HttpCacheHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * @var array<string, array{browser: int, proxy: int}>
     */
    private const PUBLIC_ROUTE_TTLS = [
        'app_main' => ['browser' => 60, 'proxy' => 300],
        'category_products' => ['browser' => 120, 'proxy' => 600],
        'product_details' => ['browser' => 120, 'proxy' => 600],
    ];

    /**
     * @var list<string>
     */
    private const PRIVATE_ROUTES = [
        'app_login',
        'app_login2',
        'app_register',
        'app_user_home',
        'show_user_address',
        'add_user_address',
        'display_basket',
        'disply_basket',
        'add_basket_product',
        'add_basket_product_count',
        'delete_basket_product',
        'delete_basket_product_legacy',
        'basket_payment',
        'order_execute',
        'admin',
        'admin_order_products',
    ];

    public static function getSubscribedEvents(): array
    {
        return [
            // Run late so it can override private/no-cache set by earlier listeners
            // on explicitly whitelisted public routes.
            KernelEvents::RESPONSE => ['onKernelResponse', -1024],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        // Never publicly cache personalized responses.
        if ($this->isPersonalizedRequest($request, $response)) {
            $this->applyPrivateNoStore($response);

            return;
        }

        $route = (string) $request->attributes->get('_route', '');
        if (isset(self::PUBLIC_ROUTE_TTLS[$route])) {
            $ttl = self::PUBLIC_ROUTE_TTLS[$route];
            $this->applyPublicCaching($response, $ttl['browser'], $ttl['proxy']);

            return;
        }

        if ($this->isPrivateRoute($route, $request->getPathInfo())) {
            $this->applyPrivateNoStore($response);
        }
    }

    private function isPersonalizedRequest(Request $request, Response $response): bool
    {
        return null !== $this->security->getUser()
            || $request->hasPreviousSession()
            || $request->headers->has('Authorization')
            || $response->headers->has('Set-Cookie');
    }

    private function isPrivateRoute(string $route, string $path): bool
    {
        if (in_array($route, self::PRIVATE_ROUTES, true)) {
            return true;
        }

        return str_starts_with($path, '/basket')
            || str_starts_with($path, '/basketkk')
            || str_starts_with($path, '/order')
            || str_starts_with($path, '/cp')
            || str_starts_with($path, '/login')
            || str_starts_with($path, '/logout')
            || str_starts_with($path, '/register')
            || str_starts_with($path, '/admin');
    }

    private function applyPublicCaching(Response $response, int $browserTtl, int $proxyTtl): void
    {
        $response->setPublic();
        $response->setMaxAge($browserTtl);
        $response->setSharedMaxAge($proxyTtl);
        $response->setVary(['Accept-Encoding'], false);

        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->addCacheControlDirective('stale-while-revalidate', '60');
        $response->headers->addCacheControlDirective('stale-if-error', '86400');
    }

    private function applyPrivateNoStore(Response $response): void
    {
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);

        $response->headers->addCacheControlDirective('no-store', true);
        $response->headers->addCacheControlDirective('no-cache', true);
        $response->headers->addCacheControlDirective('must-revalidate', true);
    }
}
