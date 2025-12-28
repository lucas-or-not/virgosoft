<?php

namespace App\Actions\Trading;

use App\Services\OrderService;

final readonly class CancelOrderAction
{
    public function __construct(
        private OrderService $orderService,
    ) {
    }

    public function execute(int $orderId, int $userId): void
    {
        $this->orderService->cancelOrder($orderId, $userId);
    }
}

