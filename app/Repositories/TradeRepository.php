<?php

namespace App\Repositories;

use App\Models\Trade;
use Illuminate\Support\Collection;

final class TradeRepository implements TradeRepositoryInterface
{
    public function create(array $data): Trade
    {
        return Trade::create($data);
    }

    /**
     * @return Collection<int, Trade>
     */
    public function getUserTrades(int $userId): Collection
    {
        return Trade::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

