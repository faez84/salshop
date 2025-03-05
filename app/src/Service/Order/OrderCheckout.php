<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Entity\Order;
use App\Event\OrderEvent;
use App\EventSubscriber\OrderEventSubscriber;
use App\Service\BasketManager;
use App\Service\Payment\IPayment;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class OrderCheckout
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        protected BasketManager $basketManager,
        protected OrderSaver $orderSaver,
        protected OrderArticleSaver $orderArticleSaver,
        protected EventDispatcherInterface $dispatcher
    ) {
        $this->dispatcher->addSubscriber(new OrderEventSubscriber($this->entityManager));
    }

    public function finalizeOrder(IPayment $payment, string $addressId): bool|string
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();
        $basket = $this->basketManager->getBasketProducts();
        try {
            $order = $this->orderSaver->save($payment->getPaymentName(), $addressId, $basket['cost']);
            $this->orderArticleSaver->save($order, $basket["products"]);
            $this->executePayment($payment, $order);
            $conn->commit();
        } catch (Exception) {
            $conn->rollBack();

            return 'Error during finalizing Order';
        }
        return true;
    }

    public function executePayment(IPayment $payment, Order $order): void
    {
        $status = 'NotFinished';
        if ($payment->executePayment()) {
            //$status = 'Finished';
            $orderEvent = new OrderEvent($order);
            $this->dispatcher->dispatch($orderEvent, OrderEvent::NAME);
            $this->basketManager->resetBasket();
            return;
        }

        $order->setStatus($status);

        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
}
