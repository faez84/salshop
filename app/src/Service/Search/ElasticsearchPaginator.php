<?php

namespace App\Service\Search;

use ApiPlatform\State\Pagination\PaginatorInterface;

class ElasticsearchPaginator implements \IteratorAggregate, PaginatorInterface
{
    private iterable $items;
    private int $currentPage;
    private int $itemsPerPage;
    private int $totalItems;

    public function __construct(iterable $items, int $currentPage, int $itemsPerPage, int $totalItems)
    {
        $this->items = $items;
        $this->currentPage = $currentPage;
        $this->itemsPerPage = $itemsPerPage;
        $this->totalItems = $totalItems;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function getTotalItems(): float|int
    {
        return $this->totalItems;
    }

    public function getCurrentPage(): float|int
    {
        return $this->currentPage;
    }

    public function getItemsPerPage(): float|int
    {
        return $this->itemsPerPage;
    }

    public function getLastPage(): float|int
    {
        return (int) ceil($this->totalItems / $this->itemsPerPage);
    }
}
