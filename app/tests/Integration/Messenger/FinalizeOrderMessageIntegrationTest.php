<?php

declare(strict_types=1);

namespace App\Tests\Integration\Messenger;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\User;
use App\Message\FinalizeOrderMessage;
use App\MessageHandler\FinalizeOrderMessageHandler;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\Foundry\Test\ResetDatabase;

final class FinalizeOrderMessageIntegrationTest extends KernelTestCase
{
    use ResetDatabase;

    public function testDispatchRoutesMessageToCheckoutAsyncTransport(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $bus = $container->get(MessageBusInterface::class);
        $transport = $container->get('messenger.transport.checkout_async');

        if (method_exists($transport, 'reset')) {
            $transport->reset();
        }

        $bus->dispatch(new FinalizeOrderMessage(1234));

        if (method_exists($transport, 'get')) {
            self::assertCount(1, $transport->get());

            return;
        }

        if (method_exists($transport, 'getSent')) {
            self::assertCount(1, $transport->getSent());

            return;
        }

        self::fail('Checkout async transport does not expose queued messages for assertions.');
    }

    public function testHandlerMarksCreditCardOrderAsFinished(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $handler = $container->get(FinalizeOrderMessageHandler::class);
        $orderRepository = $container->get(OrderRepository::class);

        $orderId = $this->createOrder('credit_card', Order::STATUS_PENDING_PAYMENT)->getId();
        self::assertIsInt($orderId);

        $handler(new FinalizeOrderMessage((int) $orderId));
        $updatedOrder = $orderRepository->find((int) $orderId);

        self::assertInstanceOf(Order::class, $updatedOrder);
        self::assertSame(Order::STATUS_FINISHED, $updatedOrder->getStatus());
    }

    public function testHandlerSkipsPaypalOrderUntilCallback(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $handler = $container->get(FinalizeOrderMessageHandler::class);
        $orderRepository = $container->get(OrderRepository::class);

        $orderId = $this->createOrder('paypal', Order::STATUS_PENDING_PAYMENT)->getId();
        self::assertIsInt($orderId);

        $handler(new FinalizeOrderMessage((int) $orderId));
        $updatedOrder = $orderRepository->find((int) $orderId);

        self::assertInstanceOf(Order::class, $updatedOrder);
        self::assertSame(Order::STATUS_PENDING_PAYMENT, $updatedOrder->getStatus());
    }

    public function testHandlerMarksUnknownPaymentAsFailed(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $handler = $container->get(FinalizeOrderMessageHandler::class);
        $orderRepository = $container->get(OrderRepository::class);

        $orderId = $this->createOrder('wire_transfer', Order::STATUS_PENDING_PAYMENT)->getId();
        self::assertIsInt($orderId);

        $handler(new FinalizeOrderMessage((int) $orderId));
        $updatedOrder = $orderRepository->find((int) $orderId);

        self::assertInstanceOf(Order::class, $updatedOrder);
        self::assertSame(Order::STATUS_PAYMENT_FAILED, $updatedOrder->getStatus());
    }

    private function createOrder(string $paymentMethod, string $status): Order
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $user = (new User())
            ->setEmail(sprintf('%s-%s@example.test', $paymentMethod, bin2hex(random_bytes(4))))
            ->setPassword('Password1!')
            ->setRoles(['ROLE_USER']);
        $entityManager->persist($user);

        $address = (new Address())
            ->setStreet('Integration Street 1')
            ->setCity('Hamburg')
            ->setZip('20095')
            ->setDefualt(true)
            ->setUser($user);
        $entityManager->persist($address);

        $order = Order::create(
            15.0,
            $paymentMethod,
            $status,
            new \DateTimeImmutable(),
            $address,
            sprintf('idem-%s-%s', $paymentMethod, bin2hex(random_bytes(4)))
        );
        $entityManager->persist($order);
        $entityManager->flush();

        return $order;
    }
}
