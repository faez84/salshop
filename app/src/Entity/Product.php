<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\Link;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/categories/{id}/products',
            uriVariables: [
                'id' => new Link(fromClass: Category::class, toProperty: 'category'),
            ],
            paginationItemsPerPage: 10,
            paginationEnabled:true,
        ),
    ]
)]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']]
)]

class Product
{
    #[Groups(["product:read"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank()]
    #[Groups(["product:read", "product:write", "category:product:read"])]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Groups(["product:read", "product:write", "category:product:read"])]
    #[ORM\Column]
    private ?float $price = null;

    #[Groups(["product:read", "product:write", "category:product:read"])]
    #[ORM\Column]
    private ?int $quantity = null;

    #[Groups(["product:read", "product:write", "category:product:read"])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[Groups(["product:read", "product:write", "category:product:read"])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;
    #[Groups(["product:read", "product:write"])]
    #[ORM\ManyToOne(inversedBy: 'products', cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[Groups(["product:read", "product:write"])]
    #[ORM\Column(length: 255)]
    private ?string $artNum = null;

    #[Groups(["product:read", "product:write"])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $features = null;

    /**
     * @var Collection<int, OrderProduct>
     */
    #[ORM\OneToMany(targetEntity: OrderProduct::class, mappedBy: 'pproduct', orphanRemoval: true)]
    private Collection $orderProducts;

    public function __construct()
    {
        $this->orderProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getArtNum(): ?string
    {
        return $this->artNum;
    }

    public function setArtNum(string $artNum): static
    {
        $this->artNum = $artNum;

        return $this;
    }

    public function getFeatures(): ?string
    {
        return $this->features;
    }

    public function setFeatures(?string $features): static
    {
        $this->features = $features;

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
            $orderProduct->setPproduct($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProduct $orderProduct): static
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getPproduct() === $this) {
                $orderProduct->setPproduct(null);
            }
        }

        return $this;
    }
}
