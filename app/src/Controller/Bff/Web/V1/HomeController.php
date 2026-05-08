<?php

declare(strict_types=1);

namespace App\Controller\Bff\Web\V1;


use App\Basket\Application\UseCase\GetBasketProducts;
use App\Catalog\Application\Port\Persistence\ICategoryRepository;
use App\Catalog\Infrastructure\Cache\CatalogCache;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly ICategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly CatalogCache $catalogCache,
        private readonly GetBasketProducts $basketProducts
    ) {
    }

    #[Route(path: '/bff/web/v1/home', name: 'bff_web_v1_home', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $categories = $this->catalogCache->getRootCategories(
            fn (): array => $this->categoryRepository->findRootCategorySummaries()
        );

        $featuredProducts = $this->resolveFeaturedProducts($categories);

        return new JsonResponse([
            'meta' => [
                'client' => 'web',
                'version' => 'v1',
                'generatedAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ],
            'categories' => array_map(
                static fn (array $category): array => [
                    'id' => (int) $category['id'],
                    'title' => (string) $category['title'],
                    'href' => sprintf('/category/%d/products', (int) $category['id']),
                ],
                $categories
            ),
            'featuredProducts' => array_map(
                static fn (array $product): array => [
                    'id' => (int) $product['id'],
                    'title' => (string) $product['title'],
                    'image' => $product['image'] ?? null,
                    'price' => (float) $product['price'],
                    'currency' => 'EUR',
                ],
                $featuredProducts
            ),
            'basket' => [
                'count' => (int) $this->basketProducts->getBasketProductsCount(),
            ],
            'ui' => [
                'showPromoBanner' => true,
            ],
        ]);
    }

    /**
     * @param array<int, array{id:int, title:string}> $categories
     *
     * @return array<int, array{id:int, title:string, image:?string, price:float}>
     */
    private function resolveFeaturedProducts(array $categories): array
    {
        if ([] === $categories) {
            return [];
        }

        $categoryId = (int) ($categories[0]['id'] ?? 0);
        if ($categoryId <= 0) {
            return [];
        }

        $products = $this->catalogCache->getCategoryProducts(
            $categoryId,
            fn (): array => $this->productRepository->findByCategoryForList($categoryId)
        );

        return array_slice($products, 0, 8);
    }
}
