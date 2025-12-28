<?php

namespace App\Actions\Trading;

use App\Repositories\OrderRepositoryInterface;
use Illuminate\Support\Collection;

final readonly class ListOrdersAction
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @return Collection<int, \App\Models\Order>
     */
    public function execute(int $userId, ?string $symbol = null): Collection
    {
        return $this->orderRepository->getUserOrders($userId, $symbol);
    }
}

