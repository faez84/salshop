<?php

declare(strict_types=1);

namespace App\Catalog\Infrastructure\Cache;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CatalogCache
{
    public const TAG_ALL = 'catalog_all';
    public const TAG_CATEGORY_TREE = 'catalog_category_tree';
    public const TAG_CATEGORY_PRODUCTS_PREFIX = 'catalog_category_';
    public const TAG_PRODUCT_PREFIX = 'catalog_product_';

    private const ROOT_CATEGORIES_TTL_SECONDS = 3600;
    private const CATEGORY_PRODUCTS_TTL_SECONDS = 300;
    private const PRODUCT_DETAIL_TTL_SECONDS = 120;

    public function __construct(
        #[Autowire(service: 'cache.app.taggable')]
        private readonly TagAwareCacheInterface $cache
    ) {
    }

    /**
     * @param callable(): array<int, array{id:int, title:string}> $loader
     *
     * @return array<int, array{id:int, title:string}>
     */
    public function getRootCategories(callable $loader): array
    {
        return $this->cache->get('catalog_categories_roots_v1', function (ItemInterface $item) use ($loader): array {
            $item->expiresAfter(self::ROOT_CATEGORIES_TTL_SECONDS);
            $item->tag([self::TAG_ALL, self::TAG_CATEGORY_TREE]);

            return $loader();
        });
    }

    /**
     * @param callable(): array<int, array{id:int, title:string, image:?string, price:float}> $loader
     *
     * @return array<int, array{id:int, title:string, image:?string, price:float}>
     */
    public function getCategoryProducts(int $categoryId, callable $loader): array
    {
        $cacheKey = sprintf('catalog_category_%d_products_v1', $categoryId);
        $categoryTag = self::TAG_CATEGORY_PRODUCTS_PREFIX . $categoryId . '_products';

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($loader, $categoryTag): array {
            $item->expiresAfter(self::CATEGORY_PRODUCTS_TTL_SECONDS);
            $item->tag([self::TAG_ALL, $categoryTag]);

            return $loader();
        });
    }



    /**
     * @param callable(): array{id:int, title:string, image:?string, artNum:string, price:float, description:?string, quantity:int, features:?string}|null $loader
     *
     * @return array{id:int, title:string, image:?string, artNum:string, price:float, description:?string, quantity:int, features:?string}|null
     */
    public function getProductDetail(int $productId, callable $loader): ?array
    {
        $cacheKey = sprintf('catalog_product_%d_detail_v1', $productId);
        $productTag = self::TAG_PRODUCT_PREFIX . $productId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($loader, $productTag): ?array {
            $item->expiresAfter(self::PRODUCT_DETAIL_TTL_SECONDS);
            $item->tag([self::TAG_ALL, $productTag]);

            return $loader();
        });
    }

    public function invalidateCatalog(): void
    {
        $this->cache->invalidateTags([self::TAG_ALL]);
    }
}
