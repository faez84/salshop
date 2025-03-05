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
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $internalProcess,
        private UserPasswordHasherInterface $userPasswordHasherInterface,
        private Security $security
    ) {
    }

    /**
     * Summary of provide
     * @param \ApiPlatform\Metadata\Operation $operation
     * @param array $uriVariables
     * @param array $context
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Address>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        /** @var User $user */
        $user = $this->security->getUser();

        return $user->getAddresses();
    }
}
