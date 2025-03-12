<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\Provider\UserAddressesProvider;
use App\State\UserAddrressPostStaeProcessor;
use App\State\UserHashPasswordStateProcessor;
use App\State\UserResetPasswordProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(operations:[
    new GetCollection(
        uriTemplate: '/users/addresses',
        normalizationContext: ['groups' => ['user:address:read']],
    ),
], provider: UserAddressesProvider::class,)]

#[ApiResource(operations:[
    new Get(
        normalizationContext: ['groups' => ['user:read']]
    ),
    new GetCollection(
        normalizationContext: ['groups' => ['user:read']]
    ),

    new Post(
        processor: UserHashPasswordStateProcessor::class,
        denormalizationContext: ['groups' => ['user:write']],
        validationContext: ['groups' => ['Default', 'postValidation']]
    ),
    new Post(
        uriTemplate: "/users/reset_password",
        processor: UserResetPasswordProcessor::class,
        denormalizationContext: ['groups' => ['user:reset']]
    )
])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(["user:read"])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(["user:read", "user:write"])]
    #[Assert\NotBlank(groups: ['postValidation'])]
    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[Groups(["user:read", "user:write"])]
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[Assert\NotBlank(groups: ['postValidation'])]
    #[Assert\Regex(['pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/"])]
    #[Groups(["user:write"])]
    #[ORM\Column]
    private ?string $password = null;

    #[Groups(['user:reset'])]
    #[Assert\Regex(['pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/"])]
    private ?string $oldPassword = null;

    #[Groups(['user:reset'])]
    #[Assert\Regex(['pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/"])]
    private ?string $newPassword = null;

    #[Groups(['user:reset'])]
    #[Assert\Expression(
        'value == this.getNewPassword()',
        message: 'Please confirm the new password'
    )]
    private ?string $repeatPassword = null;

    /**
     * @var Collection<int, Address>
     */
    #[Groups(['user:address:read'])]
    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $addresses;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getOldPassword(): ?string
    {
        return $this->oldPassword;
    }

    public function setOldPassword(string $oldPassword): static
    {
        $this->oldPassword = $oldPassword;

        return $this;
    }

    public function getNewPassword(): ?string
    {
        return $this->newPassword;
    }

    public function setNewPassword(string $newPassword): static
    {
        $this->newPassword = $newPassword;

        return $this;
    }

    public function getRepeatPassword(): ?string
    {
        return $this->repeatPassword;
    }

    public function setRepeatPassword(string $repeatPassword): static
    {
        $this->repeatPassword = $repeatPassword;

        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address)) {
            // set the owning side to null (unless already changed)
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }
}
