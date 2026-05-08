<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Persistence\Doctrine;

use App\Catalog\Infrastructure\Persistence\Doctrine\Product;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OrderProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OrderProduct implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[Groups(["order:read"])]
    #[ORM\Column]
    private ?int $amount = null;

    #[Groups(["order:read"])]
    #[ORM\Column]
    private ?float $cost = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'orderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $oorder = null;

    #[Groups(["order:read"])]
    #[ORM\ManyToOne(inversedBy: 'orderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $pproduct = null;

    private function __construct(int $amount, float $cost, Order $oorder, Product $pproduct)
    {
        $this->amount = $amount;
        $this->cost = $cost;
        $this->oorder = $oorder;
        $this->pproduct = $pproduct;
    }

    public static function create(int $amount, float $cost, Order $oorder, Product $pproduct): self
    {
        return new self($amount, $cost, $oorder, $pproduct);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

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

    public function getOOrder(): ?Order
    {
        return $this->oorder;
    }

    public function setOOrder(?Order $orderId): static
    {
        $this->oorder = $orderId;

        return $this;
    }

    public function getPproduct(): ?Product
    {
        return $this->pproduct;
    }

    public function setPproduct(?Product $pproduct): static
    {
        $this->pproduct = $pproduct;

        return $this;
    }
    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
