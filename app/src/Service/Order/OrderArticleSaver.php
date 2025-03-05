<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Order;
use App\Entity\OrderProduct;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class OrderArticleSaver
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param Order $order The order object to associate the products with
     * @param array $productIds An array containing the product IDs and corresponding data
     * @return void
     * @throws Exception If a product is out of stock
     */
    public function save(Order $order, array $productIds): void
    {
        foreach ($productIds as $productId => $productData) {
            $product = $this->entityManager->getRepository(Product::class)->find($productId);
            $amount = (int)$productData['amount'];
            $quantity = $product->getQuantity();
            if ($amount > $product->getQuantity()) {
                throw new Exception(sprintf("Product : %s is out of stock", $product->getTitle()));
            }
            $orderProduct = new OrderProduct();
            $orderProduct->setOOrder($order);
            $orderProduct->setPproduct($product);
            $orderProduct->setAmount($amount);
            $orderProduct->setCost($product->getPrice() * $amount);
            $orderProduct->setCreatedAt();
            $this->entityManager->persist($orderProduct);

            $newQuantity = $quantity - $amount;
            $product->setQuantity($newQuantity);
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();
    }
}
