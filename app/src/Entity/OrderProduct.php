<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\OrderProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: OrderProductRepository::class)]
#[ApiResource]
#[ORM\HasLifecycleCallbacks]
class OrderProduct implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[Groups(["order:read", "order:write"])]
    #[ORM\Column]
    private ?int $amount = null;

    #[Groups(["order:read", "order:write"])]
    #[ORM\Column]
    private ?float $cost = null;

    #[ORM\Column]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'orderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $oorder = null;

    #[Groups(["order:read", "order:write"])]
    #[ORM\ManyToOne(inversedBy: 'orderProducts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $pproduct = null;

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
        $pproduct->setQuantity($pproduct->getQuantity() - $this->getAmount());
        $this->pproduct = $pproduct;

        return $this;
    }
    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
