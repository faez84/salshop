<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Address;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;

class OrderSaver
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function save(string $payment, string $addressId, float $cost): Order
    {
        $address = $this->entityManager->getRepository(Address::class)->find($addressId);
        $order = new Order();

        $order->setPayment($payment);
        $order->setStatus("init");
        $order->setCost($cost);
        $order->setCreatedAt();
        $order->setAddress($address);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
