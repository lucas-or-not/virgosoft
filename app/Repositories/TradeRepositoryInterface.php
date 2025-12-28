<?php

namespace App\Repositories;

use App\Models\Trade;
use Illuminate\Support\Collection;

interface TradeRepositoryInterface
{
    public function create(array $data): Trade;

    /**
     * @return Collection<int, Trade>
     */
    public function getUserTrades(int $userId): Collection;
}

