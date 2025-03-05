<?php

declare(strict_types=1);

namespace App\Service\Product;

use App\Entity\Product;
use App\Exceptions\OutOfStockException;
use Doctrine\ORM\EntityManagerInterface;

class ProductValidator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /**
     * @param Product $product
     * @param int $amount
     * @return bool
     * @throws OutOfStockException
     */
    public function validate(Product $product, int $amount): bool
    {
        $product = $this->em->getRepository(Product::class)->find($product->getId());
        $dbAmount = $product->getQuantity();
        if ($amount > $dbAmount) {
            throw new OutOfStockException();
        }

        return true;
    }
}
