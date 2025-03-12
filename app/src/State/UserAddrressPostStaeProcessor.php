<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 *
 * @template-implements ProcessorInterface<mixed, mixed>
 *
 */
final class UserAddrressPostStaeProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $internalProcess
     * @param Security $security
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $internalProcess,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        /** @var \App\Entity\User $user */
        $user = $this->security->getUser();

        $data->setUser($user);

        return $this->internalProcess->process($data, $operation, $uriVariables, $context);
    }
}
