<?php

declare(strict_types=1);

namespace App\Address\Infrastructure\ApiPlatform;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @deprecated use UserAddressPostStateProcessor instead.
 */
final class UserAddrressPostStaeProcessor implements ProcessorInterface
{
    private UserAddressPostStateProcessor $delegate;

    /**
     * @param ProcessorInterface<mixed, mixed> $internalProcess
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        ProcessorInterface $internalProcess,
        Security $security
    ) {
        $this->delegate = new UserAddressPostStateProcessor($internalProcess, $security);
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        return $this->delegate->process($data, $operation, $uriVariables, $context);
    }
}
