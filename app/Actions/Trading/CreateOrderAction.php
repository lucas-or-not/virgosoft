<?php

namespace App\Actions\Trading;

use App\DTOs\CreateOrderDto;
use App\Models\Order;
use App\Services\OrderService;

final readonly class CreateOrderAction
{
    public function __construct(
        private OrderService $orderService,
    ) {
    }

    public function execute(CreateOrderDto $dto): Order
    {
        return $this->orderService->createOrder($dto);
    }
}

