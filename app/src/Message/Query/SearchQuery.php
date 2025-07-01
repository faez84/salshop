<?php

declare(strict_types=1);

namespace App\Message\Query;

class SearchQuery
{
    public function __construct(private string $term = "")
    {
    }

    public function getTerm(): string
    {
        return $this->term;
    }
}