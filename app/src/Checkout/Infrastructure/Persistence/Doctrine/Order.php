<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Address\Infrastructure\Persistence\Doctrine\Address;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
    ],
    security: "is_granted('ROLE_USER')",
    normalizationContext: ['groups' => ['order:read']]
)]
#[ORM\HasLifecycleCallbacks]
class Order implements \Stringable
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_PAYMENT_FAILED = 'payment_failed';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CHARGEBACK = 'chargeback';

    #[ApiProperty(identifier: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ApiProperty(identifier: true)]
    #[Groups(["order:read"])]
    #[ORM\Column(type: 'ulid', unique: true)]
    private ?Ulid $publicId = null;

    #[ORM\Column]
    #[Groups(["order:read"])]
    private ?float $cost = null;

    #[Groups(["order:read"])]
    #[ORM\Column(length: 255)]
    private ?string $payment = null;

    #[Groups(["order:read"])]
    #[ORM\Column(length: 15)]
    private ?string $status = null;

    #[Groups(["order:read"])]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $promotionCode = null;

    #[Groups(["order:read"])]
    #[ORM\Column]
    private float $discountAmount = 0.0;

    #[Groups(["order:read"])]
    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $idempotencyKey = null;

    #[ORM\Column(length: 64, nullable: true, unique: true)]
    private ?string $providerOrderId = null;

    /**
     * @var Collection<int, OrderProduct>
     */
    #[Groups(["order:read"])]
    #[ORM\OneToMany(targetEntity: OrderProduct::class, mappedBy: 'oorder', cascade:["persist"], orphanRemoval: true)]
    private Collection $orderProducts;

    #[Groups(["order:read"])]
    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Address $address = null;

    private function __construct(
        float $cost,
        string $payment,
        string $status,
        ?DateTimeImmutable $createdAt,
        ?Address $address,
        ?string $idempotencyKey
    )
     {
        $this->cost = $cost;
        $this->payment = $payment;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->address = $address;
        $this->idempotencyKey = $idempotencyKey;
        $this->orderProducts = new ArrayCollection();
    }

    public static function create(
        float $cost,
        string $payment,
        string $status,
        ?DateTimeImmutable $createdAt,
        ?Address $address,
        ?string $idempotencyKey
    ): self
    {
        return new self($cost, $payment, $status, $createdAt, $address, $idempotencyKey);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicId(): ?Ulid
    {
        return $this->publicId;
    }

    public function setPublicId(Ulid|string $publicId): static
    {
        $this->publicId = \is_string($publicId) ? Ulid::fromString($publicId) : $publicId;

        return $this;
    }

    public function getCost(): ?float
    {
        return $this->cost;
    }

    public function setCost(float $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getPayment(): ?string
    {
        return $this->payment;
    }

    public function setPayment(string $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): static
    {
        $this->createdAt =  new DateTimeImmutable();

        return $this;
    }

    #[ORM\PrePersist]
    public function setPublicIdOnCreate(): static
    {
        $this->publicId ??= new Ulid();

        return $this;
    }

    public function getPromotionCode(): ?string
    {
        return $this->promotionCode;
    }

    public function setPromotionCode(?string $promotionCode): static
    {
        $this->promotionCode = $promotionCode;

        return $this;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(float $discountAmount): static
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->idempotencyKey;
    }

    public function setIdempotencyKey(?string $idempotencyKey): static
    {
        $this->idempotencyKey = $idempotencyKey;

        return $this;
    }

    public function getProviderOrderId(): ?string
    {
        return $this->providerOrderId;
    }

    public function setProviderOrderId(?string $providerOrderId): static
    {
        $this->providerOrderId = $providerOrderId;

        return $this;
    }

    /**
     * @return Collection<int, OrderProduct>
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProduct $orderProduct): static
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->add($orderProduct);
            $orderProduct->setOOrder($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): static
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getOOrder() === $this) {
                $orderProduct->setOOrder(null);
            }
        }

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
