<?php

declare(strict_types=1);

namespace App\Basket\Domain\Service;

use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use App\Checkout\Application\Exceptions\OutOfStockException;
use Doctrine\ORM\EntityManagerInterface;

class BasketValidator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function validate(int $productId, int $amount): bool
    {
        $product = $this->em->getRepository(Product::class)->find($productId);
        if (!$product instanceof Product) {
            throw new OutOfStockException();
        }

        $dbAmount = $product->getQuantity();
        if ($amount > $dbAmount) {
            throw new OutOfStockException();
        }

        return true;
    }
}
