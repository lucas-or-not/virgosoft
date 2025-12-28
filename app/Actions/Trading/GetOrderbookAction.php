<?php

namespace App\Actions\Trading;

use App\DTOs\OrderbookEntryDto;
use App\Models\Order;
use App\Repositories\OrderRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final readonly class GetOrderbookAction
{
    private const CACHE_TTL = 2; 
    private const CACHE_PREFIX = 'orderbook:';

    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @return array{buy: array<int, array<string, string>>, sell: array<int, array<string, string>>}
     */
    public function execute(?string $symbol): array
    {
        $cacheKey = $this->getCacheKey($symbol);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($symbol) {
            return $this->buildOrderbook($symbol);
        });
    }

    public function getCacheKey(?string $symbol): string
    {
        $symbolKey = $symbol ?? 'all';
        return self::CACHE_PREFIX . $symbolKey;
    }

    /**
     * @return array{buy: array<int, array<string, string>>, sell: array<int, array<string, string>>}
     */
    private function buildOrderbook(?string $symbol): array
    {
        $orders = $this->orderRepository->findOpenOrdersForSymbol($symbol);

        $buyOrders = $orders->filter(fn (Order $order) => $order->isBuy())
            ->sortByDesc('price')
            ->map(fn (Order $order) => new OrderbookEntryDto(
                price: (string) $order->price,
                amount: (string) $order->amount,
                side: Order::SIDE_BUY,
                symbol: $symbol === null ? $order->symbol : null,
            ))
            ->map(fn (OrderbookEntryDto $dto) => $dto->toArray())
            ->values()
            ->toArray();

        $sellOrders = $orders->filter(fn (Order $order) => $order->isSell())
            ->sortBy('price')
            ->map(fn (Order $order) => new OrderbookEntryDto(
                price: (string) $order->price,
                amount: (string) $order->amount,
                side: Order::SIDE_SELL,
                symbol: $symbol === null ? $order->symbol : null,
            ))
            ->map(fn (OrderbookEntryDto $dto) => $dto->toArray())
            ->values()
            ->toArray();

        return [
            'buy' => $buyOrders,
            'sell' => $sellOrders,
        ];
    }
}

