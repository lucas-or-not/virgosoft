<?php

namespace App\Listeners;

use App\Actions\Trading\GetOrderbookAction;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderMatched;
use Illuminate\Support\Facades\Cache;

class InvalidateOrderbookCache
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly GetOrderbookAction $getOrderbookAction,
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated|OrderMatched|OrderCancelled $event): void
    {
        // Get symbol from the event
        $symbol = match (true) {
            $event instanceof OrderCreated => $event->order->symbol,
            $event instanceof OrderMatched => $event->buyOrder->symbol,
            $event instanceof OrderCancelled => $event->order->symbol,
        };

        // Invalidate cache for the specific symbol and 'all' cache
        $this->invalidateCache($symbol);
        $this->invalidateCache(null); 
    }

        /**
     * Invalidate orderbook cache for a specific symbol or all symbols.
     */
    private function invalidateCache(?string $symbol = null): void
    {
        if ($symbol !== null) {
            // Invalidate specific symbol
            Cache::forget($this->getOrderbookAction->getCacheKey($symbol));
        } else {
            // Invalidate all symbols (BTC, ETH, and 'all')
            Cache::forget($this->getOrderbookAction->getCacheKey('BTC'));
            Cache::forget($this->getOrderbookAction->getCacheKey('ETH'));
            Cache::forget($this->getOrderbookAction->getCacheKey(null));
        }
    }
}
