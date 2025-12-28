<?php

namespace App\Repositories;

use App\Models\Asset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AssetRepository implements AssetRepositoryInterface
{
    public function findByUserAndSymbol(int $userId, string $symbol): ?Asset
    {
        return Asset::where('user_id', $userId)
            ->where('symbol', $symbol)
            ->first();
    }

    public function findOrCreateByUserAndSymbol(int $userId, string $symbol): Asset
    {
        $asset = Asset::where('user_id', $userId)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();
        
        if ($asset === null) {
            $asset = Asset::create([
                'user_id' => $userId,
                'symbol' => $symbol,
                'amount' => 0,
                'locked_amount' => 0,
            ]);
        }
        
        return $asset;
    }

    public function lockAmount(Asset $asset, string $amount): void
    {
        $asset->increment('locked_amount', $amount);
    }

    public function unlockAmount(Asset $asset, string $amount): void
    {
        $asset->decrement('locked_amount', $amount);
    }

    public function addAmount(Asset $asset, string $amount): void
    {
        $asset->increment('amount', $amount);
    }

    public function subtractAmount(Asset $asset, string $amount): void
    {
        $asset->decrement('amount', $amount);
    }

    /**
     * @return Collection<int, Asset>
     */
    public function getUserAssets(int $userId): Collection
    {
        return Asset::where('user_id', $userId)->get();
    }
}

