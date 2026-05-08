<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;


use App\Catalog\Infrastructure\Cache\CatalogCache;
use App\Catalog\Infrastructure\Persistence\Doctrine\Category;
use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use App\Checkout\Infrastructure\Persistence\Doctrine\Promotion;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class CatalogCacheInvalidationSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly CatalogCache $catalogCache
    ) {
    }

    /**
     * @return list<string>
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->invalidateIfCatalogEntity($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->invalidateIfCatalogEntity($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->invalidateIfCatalogEntity($args);
    }

    private function invalidateIfCatalogEntity(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (
            !$entity instanceof Product
            && !$entity instanceof Category
            && !$entity instanceof Promotion
        ) {
            return;
        }

        $this->catalogCache->invalidateCatalog();
    }
}
