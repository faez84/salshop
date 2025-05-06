<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Message\Command\OrderFinalize;
use Doctrine\Common\Collections\Collection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\String\ByteString;

/**
 *
 * @template-implements ProcessorInterface<mixed, mixed>
 *
 */
final class CreateOrderProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $internalProcess
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $internalProcess,
        private MessageBusInterface $messageBus
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $data->setOrderNr(ByteString::fromRandom(8)->toString());
        $this->messageBus->dispatch(new OrderFinalize($data->getOrderNr(), $data->getPayment()));
        return $this->internalProcess->process($data, $operation, $uriVariables, $context);
    }
}
