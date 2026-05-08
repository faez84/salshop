<?php

declare(strict_types=1);

namespace App\Checkout\Domain\Entity;

use App\Checkout\Domain\ValueObject\PaymentMethod;
use App\Checkout\Infrastructure\Persistence\Doctrine\Order as DoctrineOrder;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class Order
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_PAYMENT_FAILED = 'payment_failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CHARGEBACK = 'chargeback';

    public function __construct(private readonly DoctrineOrder $dtoOrder)
    {
    }

    public static function fromPersistence(DoctrineOrder $dtoOrder): self
    {
        return new self($dtoOrder);
    }

    public function getId(): int
    {
        return (int) $this->dtoOrder->getId();
    }

    public function getStatus(): string
    {
        return (string) $this->dtoOrder->getStatus();
    }

    public function getCost(): float
    {
        return (float) $this->dtoOrder->getCost();
    }

    public function assignProviderOrderId(string $providerOrderId): void
    {
        $providerOrderId = trim($providerOrderId);
        if ('' === $providerOrderId) {
            throw new \InvalidArgumentException('Provider order id must not be empty.');
        }

        $this->dtoOrder->setProviderOrderId($providerOrderId);
    }

    public function getPayment(): ?string
    {
        return $this->dtoOrder->getPayment();
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->dtoOrder->getIdempotencyKey();
    }

    public function isFinished(): bool
    {
        return self::STATUS_FINISHED === $this->getStatus();
    }

    public function isPaymentFailed(): bool
    {
        return self::STATUS_PAYMENT_FAILED === $this->getStatus();
    }

    public function isPendingPayment(): bool
    {
        return self::STATUS_PENDING_PAYMENT === $this->getStatus();
    }

    public function isSettled(): bool
    {
        return \in_array($this->getStatus(), [self::STATUS_REFUNDED, self::STATUS_CHARGEBACK], true);
    }

    public function isPaypalPayment(): bool
    {
        return PaymentMethod::isPaypal((string) $this->getPayment());
    }

    public function toPersistence(): DoctrineOrder
    {
        return $this->dtoOrder;
    }

    public function getDto(): DoctrineOrder
    {
        return $this->toPersistence();
    }

    public function setProviderOrderId(string $providerOrderId): void
    {
        $this->assignProviderOrderId($providerOrderId);
    }
}
