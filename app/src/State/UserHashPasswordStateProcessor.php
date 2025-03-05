<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserHashPasswordStateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        readonly private ProcessorInterface $internalProcess,
        readonly private UserPasswordHasherInterface $userPasswordHasherInterface
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (!$data->getPassword()) {
            return $this->internalProcess->process($data, $operation, $uriVariables, $context);
        }

        if ($data instanceof User) {
            // Handle the state
            $hashedPassword = $this->userPasswordHasherInterface->hashPassword($data, $data->getPassword());
            $data->setPassword($hashedPassword);
        }
        $data->eraseCredentials();
        return $this->internalProcess->process($data, $operation, $uriVariables, $context);
    }
}
