<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Address;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

class OrderSaver
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function save(string $payment, string $addressId, float $cost): Order
    {
        $address = $this->entityManager->getRepository(Address::class)->find($addressId);
        if (!$address instanceof Address) {
            throw new RuntimeException(sprintf('Address with ID %s was not found.', $addressId));
        }

        $order = new Order();

        $order->setPayment($payment);
        $order->setStatus(Order::STATUS_PENDING_PAYMENT);
        $order->setCost($cost);
        $order->setCreatedAt();
        $order->setAddress($address);
        $this->entityManager->persist($order);

        return $order;
    }
}
