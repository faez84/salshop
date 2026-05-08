<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Basket;

use App\Repository\ProductRepository;
use App\Service\Basket\BasketManager;
use App\Service\Basket\BasketValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class BasketManagerTest extends TestCase
{
    public function testAddToBasketUsesScalarQuantitiesAndValidatesCurrentAmount(): void
    {
        [$requestStack, $session] = $this->createRequestStackWithSession();
        $productRepository = $this->createMock(ProductRepository::class);
        $basketValidator = $this->createMock(BasketValidator::class);
        $basketValidator
            ->expects(self::exactly(2))
            ->method('validate')
            ->withConsecutive([5, 1], [5, 2]);

        $basketManager = new BasketManager($requestStack, $productRepository, $basketValidator);
        $basketManager->addToBasket(5);
        $basketManager->addToBasket(5);

        self::assertSame(
            ['products' => [5 => 2]],
            $session->get('basket')
        );
        self::assertSame(2, $basketManager->getProductCount(5));
    }

    public function testDeleteFromBasketClearsSessionWhenLastProductRemoved(): void
    {
        [$requestStack, $session] = $this->createRequestStackWithSession();
        $productRepository = $this->createMock(ProductRepository::class);
        $basketValidator = $this->createMock(BasketValidator::class);

        $basketManager = new BasketManager($requestStack, $productRepository, $basketValidator);
        $session->set('basket', ['products' => [7 => 1]]);

        $basketManager->deleteFromBasket(7);

        self::assertNull($session->get('basket'));
    }

    public function testGetBasketProductsListReturnsEmptyWhenBasketContainsNoIds(): void
    {
        [$requestStack, $session] = $this->createRequestStackWithSession();
        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects(self::never())->method('findInValues');
        $basketValidator = $this->createMock(BasketValidator::class);

        $basketManager = new BasketManager($requestStack, $productRepository, $basketValidator);
        $session->set('basket', ['products' => []]);

        self::assertSame([], $basketManager->getBasketProductsList());
    }

    public function testGetBasketProductsCountReturnsSummedScalarAmounts(): void
    {
        [$requestStack, $session] = $this->createRequestStackWithSession();
        $productRepository = $this->createMock(ProductRepository::class);
        $basketValidator = $this->createMock(BasketValidator::class);

        $basketManager = new BasketManager($requestStack, $productRepository, $basketValidator);
        $session->set('basket', ['products' => [1 => 2, 2 => 3]]);

        self::assertSame(5, $basketManager->getBasketProductsCount());
    }

    /**
     * @return array{0: RequestStack, 1: Session}
     */
    private function createRequestStackWithSession(): array
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return [$requestStack, $session];
    }
}
