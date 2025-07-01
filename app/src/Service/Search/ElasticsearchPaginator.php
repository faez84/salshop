<?php

namespace App\Service\Search;

use ApiPlatform\State\Pagination\PaginatorInterface;

class ElasticsearchPaginator implements \IteratorAggregate, PaginatorInterface
{
    private array $items;
    private int $currentPage;
    private int $itemsPerPage;
    private int $totalItems;

    public function __construct(array $items = [], int $currentPage = 1, int $itemsPerPage = 10, int $totalItems = 100)
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

    public function getCurrentPage(): float
    {
        return (float) $this->currentPage;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->itemsPerPage;
    }

    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }

    function count(): int
    {
        return $this->totalItems;
    }

    public function getLastPage(): float
    {
        return (int) ceil($this->totalItems / $this->itemsPerPage);
    }
}
