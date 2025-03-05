<?php

declare(strict_types=1);

namespace App\Service\Basket;

use App\Entity\Product;
use App\Exceptions\OutOfStockException;
use Doctrine\ORM\EntityManagerInterface;

class BasketValidator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function validate(int $productId, int $amount): bool
    {
        $product = $this->em->getRepository(Product::class)->find($productId);
        $dbAmount = $product->getQuantity();
        if ($amount > $dbAmount) {
            throw new OutOfStockException();
        }

        return true;
    }
}
