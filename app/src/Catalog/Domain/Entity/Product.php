<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Entity;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class Product
{
    public function __construct(private \App\Catalog\Infrastructure\Persistence\Doctrine\Product $productDto)
    {
    
    }

    public function getId(): int
    {
        return $this->productDto->getId();
    }

    public function getName(): string
    {
        return $this->productDto->getTitle();
    }

    public function getPrice(): float
    {
        return $this->productDto->getPrice();
    }
}
