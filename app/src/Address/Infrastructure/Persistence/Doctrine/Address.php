<?php

declare(strict_types=1);

namespace App\Address\Infrastructure\Persistence\Doctrine;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Address\Infrastructure\ApiPlatform\UserAddressPostStateProcessor;
use App\Checkout\Infrastructure\Persistence\Doctrine\Order;
use App\User\Infrastructure\Persistence\Doctrine\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ApiResource(operations:[
    new Get(
        normalizationContext: ['groups' => ['user:address:read']],
        security: "is_granted('ROLE_ADMIN') or object.getUser() == user",
    ),
    new GetCollection(
        uriTemplate: '/users/addresses',
        normalizationContext: ['groups' => ['user:address:read']],
        security: "is_granted('ROLE_USER')",
    ),
    new Post(
        processor: UserAddressPostStateProcessor::class,
        uriTemplate: '/users/addresses',
        denormalizationContext: ['groups' => ['user:address:write']],
        normalizationContext: ['groups' => ['user:address:read']],
        security: "is_granted('ROLE_USER')",
    ),
])]

class Address
{
    #[Groups(["user:address:read"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank()]
    #[Groups(["user:address:read", "user:address:write"])]
    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[Assert\NotBlank()]
    #[Groups(["user:address:read", "user:address:write"])]
    #[ORM\Column(length: 100)]
    private ?string $city = null;
    #[Assert\NotBlank()]
    #[Groups(["user:address:read", "user:address:write"])]
    #[ORM\Column(length: 15)]
    private ?string $zip = null;
    #[Groups(["user:address:write"])]
    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;
    #[Groups(["user:address:write"])]
    #[ORM\Column(name: 'defualt')]
    private ?bool $isDefault = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'address')]
    private Collection $orders;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): static
    {
        $this->zip = $zip;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function setIsDefault(bool $isDefault): static
    {
        return $this->setDefault($isDefault);
    }

    public function setDefualt(bool $defualt): static
    {
        return $this->setDefault($defualt);
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setAddress($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getAddress() === $this) {
                $order->setAddress(null);
            }
        }

        return $this;
    }
}
