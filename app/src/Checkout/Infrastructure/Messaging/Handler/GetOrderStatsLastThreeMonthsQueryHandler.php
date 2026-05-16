<?php

declare(strict_types=1);

namespace App\Checkout\Infrastructure\Messaging\Handler;

use App\Checkout\Application\Port\Persistence\IOrderRepository;
use App\Checkout\Infrastructure\Messaging\Query\GetOrderStatsLastThreeMonthsQuery;
use App\Checkout\Infrastructure\Messaging\Query\OrderStatsPointView;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final class GetOrderStatsLastThreeMonthsQueryHandler
{
    public function __construct(
        private readonly IOrderRepository $orderRepository,
    ) {
    }

    /**
     * @return array<int, OrderStatsPointView>
     */
    public function __invoke(GetOrderStatsLastThreeMonthsQuery $query): array
    {
        $rows = $this->orderRepository->findOrderInLastThreeMonths();
        $stats = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $stats[] = new OrderStatsPointView(
                orderCount: max(0, (int) ($row['orderCount'] ?? 0)),
                dateAsDay: (string) ($row['dateAsDay'] ?? '')
            );
        }

        return $stats;
    }
}
