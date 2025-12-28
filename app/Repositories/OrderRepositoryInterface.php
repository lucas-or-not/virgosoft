<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function create(array $data): Order;

    public function findById(int $id): ?Order;

    public function findByIdOrFail(int $id): Order;

    /**
     * @return Collection<int, Order>
     */
    public function findOpenOrdersForSymbol(?string $symbol): Collection;

    public function findMatchingBuyOrder(string $symbol, string $price): ?Order;

    public function findMatchingSellOrder(string $symbol, string $price): ?Order;

    /**
     * @return Collection<int, Order>
     */
    public function getUserOrders(int $userId, ?string $symbol = null): Collection;

    public function updateStatus(Order $order, int $status): void;
}

