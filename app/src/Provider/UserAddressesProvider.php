<?php

declare(strict_types=1);

namespace App\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Pagination\PartialPaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\Collection;

/**
 * @template-implements ProviderInterface<Collection>
 */
class UserAddressesProvider implements ProviderInterface
{
    /**
     * @param Security $security
     */
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * * @param array<string, mixed>|array{request?: Request, resource_class?: string} $context
     * @return object|array|object[]|null
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $user->getAddresses();
    }
}
