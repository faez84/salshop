<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use App\Address\Infrastructure\Persistence\Doctrine\Address;
use App\Checkout\Application\Port\IOrderSaver;
use App\Checkout\Application\Port\Persistence\IOrderRepository;
use App\Checkout\Infrastructure\Persistence\Doctrine\Order;
use App\Exceptions\CheckoutValidationException;
use App\User\Infrastructure\Persistence\Doctrine\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrderSaver implements IOrderSaver
{
    public function __construct(
        readonly protected EntityManagerInterface $entityManager,
        readonly protected IOrderRepository $orderRepository,
        private readonly Security $security
        )
    {
    }

    public function save(
        string $payment,
        string $addressId,
        float $cost,
        string $idempotencyKey,
        ?string $promotionCode = null,
        float $discountAmount = 0.0
    ): Order
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            throw new CheckoutValidationException('You must be logged in to place an order.');
        }

        $address = $this->entityManager->getRepository(Address::class)->find($addressId);
        if (!$address instanceof Address) {
            throw new CheckoutValidationException(sprintf('Address with ID %s was not found.', $addressId));
        }

        if ($address->getUser()?->getId() !== $currentUser->getId()) {
            throw new CheckoutValidationException('Selected address does not belong to the current user.');
        }

        $order = Order::create($cost, $payment, Order::STATUS_PENDING_PAYMENT, null, $address, $idempotencyKey);
        $order
            ->setPromotionCode($promotionCode)
            ->setDiscountAmount($discountAmount);

        return $this->orderRepository->save($order, true);
    }
}
