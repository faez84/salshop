<?php

declare(strict_types=1);

namespace App\Catalog\Application\Port\Persistence;

interface ICategoryRepository
{
    public function findRootCategorySummaries(): array;
}
