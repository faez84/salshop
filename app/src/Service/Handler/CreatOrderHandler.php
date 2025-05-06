<?php

declare(strict_types=1);

namespace App\Service\Handler;

use ApiPlatform\Metadata\IriConverterInterface;
use App\Entity\Address;
use App\Entity\Order;
use App\Entity\OrderProduct;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\String\ByteString;

class CreatOrderHandler {
    
    public function __construct(private IriConverterInterface $iriConverter, private EntityManagerInterface $em) {
        
    }

    public function handle(array $data): void 
    {
        $order = new Order();
        $address = $this->iriConverter->getResourceFromIri($data["address"]);
        $order->setAddress($address);
        $order->setCost($data["cost"]);
        $order->setPayment($data["payment"]);
        $order->setStatus($data["status"]);
        foreach ($data["orderProducts"] as $product) {
            $orderProduct = new OrderProduct();
            $orderProduct->setCost($product["cost"]);
            $orderProduct->setAmount($product["amount"]);
            /** $pproduct Product */
            $pproduct = $this->iriConverter->getResourceFromIri($product["pproduct"]);

            var_dump("----------");
            var_dump($pproduct->getQuantity());
            var_dump($product["amount"]);
           
            $orderProduct->setPproduct($pproduct);
            $order->addOrderProduct($orderProduct);
        }
        $order->setOrderNr(ByteString::fromRandom(8)->toString());
        $this->em->persist($order);
        $this->em->flush();

    }
}