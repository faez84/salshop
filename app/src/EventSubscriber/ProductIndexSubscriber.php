<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Product;
use App\Service\Search\ProductIndexer;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ProductIndexSubscriber implements EventSubscriber
{
    public function __construct(private readonly ProductIndexer $indexer) {}

    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate, Events::postRemove];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleIndexing($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleIndexing($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if ($entity instanceof Product) {
            $this->indexer->delete($entity->getId());
        }
    }

    private function handleIndexing(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();
        if ($entity instanceof Product) {
            $this->indexer->index($entity);
        }
    }
}