<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Handler;

use App\Checkout\Application\Port\Persistence\IOrderRepository;
use App\Checkout\Infrastructure\Messaging\Query\GetOrderSummaryQuery;
use App\Checkout\Infrastructure\Messaging\Query\OrderSummaryView;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final class GetOrderSummaryQueryHandler
{
    public function __construct(
        private readonly IOrderRepository $orderRepository,
    ) {
    }

    public function __invoke(GetOrderSummaryQuery $query): ?OrderSummaryView
    {
        $orderId = $query->getOrderId();
        if ($orderId <= 0) {
            return null;
        }

        $order = $this->orderRepository->findById($orderId);
        if (null === $order) {
            return null;
        }

        return new OrderSummaryView(
            orderId: (int) $order->getId(),
            status: (string) $order->getStatus(),
            paymentMethod: (string) ($order->getPayment() ?? ''),
            cost: (float) ($order->getCost() ?? 0.0),
            idempotencyKey: $order->getIdempotencyKey()
        );
    }
}
