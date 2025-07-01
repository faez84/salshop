<?php

namespace App\MessageHandler\Command;

use App\Entity\Order;
use App\Message\Command\OrderFinalize;
use Doctrine\ORM\EntityManagerInterface;

class OrderFinalizeHandler
{
    public function __construct(Private EntityManagerInterface $entityManager)
    {
    }
    public function __invoke(OrderFinalize $orderFinalize): void
    {
       
    }
}