<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Support\Collection;

final class OrderRepository implements OrderRepositoryInterface
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function findById(int $id): ?Order
    {
        return Order::find($id);
    }

    public function findByIdOrFail(int $id): Order
    {
        return Order::findOrFail($id);
    }

    /**
     * @return Collection<int, Order>
     */
    public function findOpenOrdersForSymbol(?string $symbol): Collection
    {
        $query = Order::where('status', Order::STATUS_OPEN);

        if ($symbol !== null) {
            $query->where('symbol', $symbol);
        }

        return $query->orderBy('created_at')->get();
    }

    public function findMatchingBuyOrder(string $symbol, string $price): ?Order
    {
        return Order::where('symbol', $symbol)
            ->where('side', Order::SIDE_BUY)
            ->where('status', Order::STATUS_OPEN)
            ->where('price', '>=', $price)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();
    }

    public function findMatchingSellOrder(string $symbol, string $price): ?Order
    {
        return Order::where('symbol', $symbol)
            ->where('side', Order::SIDE_SELL)
            ->where('status', Order::STATUS_OPEN)
            ->where('price', '<=', $price)
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();
    }

    /**
     * @return Collection<int, Order>
     */
    public function getUserOrders(int $userId, ?string $symbol = null): Collection
    {
        $query = Order::where('user_id', $userId);

        if ($symbol !== null) {
            $query->where('symbol', $symbol);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function updateStatus(Order $order, int $status): void
    {
        $order->update(['status' => $status]);
    }
}

