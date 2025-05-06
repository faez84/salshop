<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Elasticsearch\Filter\MatchFilter;
use ApiPlatform\Elasticsearch\State\CollectionProvider;
use ApiPlatform\Elasticsearch\State\ItemProvider;
use ApiPlatform\Elasticsearch\State\Options;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\ProductRepository;
use App\Service\Search\ElasticsearchProductProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\QueryParameter;


#[ApiResource(
    operations: [
      //  new Get(uriTemplate: 'productselastic', provider: ItemProvider::class, stateOptions: new Options(index: 'productselastic')),
        new GetCollection(
            uriTemplate: 'productselastic',
            provider: ElasticsearchProductProvider::class, stateOptions: new Options(index: 'productselastic'),
            paginationItemsPerPage: 20,
            paginationEnabled:true,
           // parameters: [
            //'order[:property]' => new QueryParameter(filter: 'product.order_filter'),
            //'fooAlias' => new QueryParameter(filter: 'app_search_filter_via_parameter', property: 'artNum'),
       // ]
    ),
    new Post(security: "is_granted('ROLE_ADMIN')"),
    new Put(security: "is_granted('ROLE_ADMIN')"),
    new Patch(security: "is_granted('ROLE_ADMIN')"),
    new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['productselastic:read']],
)]

#[ApiFilter(SearchFilter::class, properties: [
    'title' => 'partial',
    'artNum' => 'exact',
    'price' => 'exact',
    'quantity' => 'exact',
    'category' => 'partial',
])]

class ProductElastic
{
    #[ApiProperty(identifier: true)]
    #[Groups(['productselastic:read'])]
    public ?int $id = null;

   // #[ApiFilter(SearchFilter::class, strategy: 'partial')]
    #[Assert\NotBlank()]
    #[Groups(['productselastic:read'])]
    private ?string $title = null;

    #[Groups(["productselastic:read", "productselastic:write", "category:product:read"])]
    private ?float $price = null;

    #[Groups(["productselastic:read", "productselastic:write", "category:product:read"])]
    private ?int $quantity = null;
    #[Groups(['productselastic:read'])]
    private ?string $description = null;

    #[Groups(["productselastic:read", "productselastic:write", "category:product:read"])]
    private ?string $image = null;

    #[Groups(["productselastic:read", "productselastic:write"])]
    private ?string $artNum = null;

    #[Groups(["productselastic:read", "productselastic:write"])]
    private ?string $features = null;

    #[Groups(["productselastic:read", "productselastic:write"])]
    #[ORM\ManyToOne(inversedBy: 'products', cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    public function __construct()
    {
        $this->orderProducts = new ArrayCollection();
    }

    #[Groups(['productselastic:read'])]
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
