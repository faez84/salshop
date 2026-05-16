<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Handler;

use App\Checkout\Infrastructure\Message\Query\OrderFinalizedEventQuery;
use Psr\Log\LoggerInterface;

class OrderFinalizedEventQueryHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(OrderFinalizedEventQuery $query): void
    {
        
    $this->logger->info('Received Order %s with status %s', [
            'orderId' => $query->getOrderId(),
            'status' => $query->getStatus(),
        ]);
    }
}
