<?php

declare(strict_types=1);

namespace App\Basket\Infrastructure\Store;

use App\Basket\Application\Port\IBasketStore;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionBasketStore implements IBasketStore
{
    private const SESSION_KEY = 'basket';

    private SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    public function getBasket(): ?array
    {
        $basket = $this->session->get(self::SESSION_KEY);
        if (!is_array($basket)) {
            return null;
        }

        return $basket;
    }

    public function saveBasket(array $basket): void
    {
        $this->session->set(self::SESSION_KEY, $basket);
    }

    public function clearBasket(): void
    {
        $this->session->remove(self::SESSION_KEY);
    }

    public function resetBasket(): void
    {
        $this->session->remove('basket');
    }
}

