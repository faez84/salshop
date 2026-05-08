<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Order;

use App\Entity\Address;
use App\Entity\Order;
use App\Entity\User;
use App\Exceptions\CheckoutValidationException;
use App\Repository\OrderRepository;
use App\Service\Order\OrderSaver;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class OrderSaverTest extends TestCase
{
    public function testSaveFailsWhenUserIsNotAuthenticated(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $orderRepository = $this->createMock(OrderRepository::class);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $orderSaver = new OrderSaver($entityManager, $orderRepository, $security);

        $this->expectException(CheckoutValidationException::class);
        $this->expectExceptionMessage('You must be logged in to place an order.');

        $orderSaver->save('credit_card', '100', 50.0, 'idem-100');
    }

    public function testSaveFailsWhenAddressDoesNotBelongToCurrentUser(): void
    {
        $owner = $this->createUser('owner@example.com', 1);
        $otherUser = $this->createUser('other@example.com', 2);

        $address = (new Address())->setUser($otherUser);
        $addressRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $addressRepository
            ->expects(self::once())
            ->method('find')
            ->with('200')
            ->willReturn($address);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Address::class)
            ->willReturn($addressRepository);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository->expects(self::never())->method('save');

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($owner);

        $orderSaver = new OrderSaver($entityManager, $orderRepository, $security);

        $this->expectException(CheckoutValidationException::class);
        $this->expectExceptionMessage('Selected address does not belong to the current user.');

        $orderSaver->save('credit_card', '200', 10.0, 'idem-200');
    }

    public function testSaveReturnsCreatedOrderWhenAddressOwnershipIsValid(): void
    {
        $owner = $this->createUser('owner@example.com', 3);
        $address = (new Address())->setUser($owner);

        $addressRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['find'])
            ->getMock();
        $addressRepository
            ->expects(self::once())
            ->method('find')
            ->with('300')
            ->willReturn($address);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Address::class)
            ->willReturn($addressRepository);

        $orderRepository = $this->createMock(OrderRepository::class);
        $orderRepository
            ->expects(self::once())
            ->method('save')
            ->with(
                self::callback(function (Order $order) use ($address): bool {
                    return $order->getPayment() === 'credit_card'
                        && $order->getStatus() === Order::STATUS_PENDING_PAYMENT
                        && $order->getAddress() === $address
                        && $order->getCost() === 33.0
                        && $order->getIdempotencyKey() === 'idem-300';
                }),
                true
            )
            ->willReturnCallback(static fn (Order $order): Order => $order);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($owner);

        $orderSaver = new OrderSaver($entityManager, $orderRepository, $security);
        $order = $orderSaver->save('credit_card', '300', 33.0, 'idem-300');

        self::assertSame('credit_card', $order->getPayment());
        self::assertSame(Order::STATUS_PENDING_PAYMENT, $order->getStatus());
        self::assertSame($address, $order->getAddress());
    }

    private function createUser(string $email, int $id): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setPassword('Password1!');

        $idProperty = new \ReflectionProperty(User::class, 'id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($user, $id);

        return $user;
    }
}
