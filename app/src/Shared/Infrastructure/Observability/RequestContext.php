<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Observability;

use Symfony\Component\HttpFoundation\Request;

final class RequestContext
{
    private ?string $requestId = null;
    private ?string $route = null;
    private ?string $method = null;
    private ?string $path = null;

    public function setFromRequest(Request $request, string $requestId): void
    {
        $this->requestId = $requestId;
        $this->route = (string) $request->attributes->get('_route', '');
        $this->method = $request->getMethod();
        $this->path = $request->getPathInfo();
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $context = [];
        if (null !== $this->requestId && '' !== $this->requestId) {
            $context['request_id'] = $this->requestId;
        }
        if (null !== $this->route && '' !== $this->route) {
            $context['request_route'] = $this->route;
        }
        if (null !== $this->method && '' !== $this->method) {
            $context['request_method'] = $this->method;
        }
        if (null !== $this->path && '' !== $this->path) {
            $context['request_path'] = $this->path;
        }

        return $context;
    }

    public function clear(): void
    {
        $this->requestId = null;
        $this->route = null;
        $this->method = null;
        $this->path = null;
    }
}
