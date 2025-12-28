<?php

namespace App\Repositories;

use App\Models\Asset;
use Illuminate\Support\Collection;

interface AssetRepositoryInterface
{
    public function findByUserAndSymbol(int $userId, string $symbol): ?Asset;

    public function findOrCreateByUserAndSymbol(int $userId, string $symbol): Asset;

    public function lockAmount(Asset $asset, string $amount): void;

    public function unlockAmount(Asset $asset, string $amount): void;

    public function addAmount(Asset $asset, string $amount): void;

    public function subtractAmount(Asset $asset, string $amount): void;

    /**
     * @return Collection<int, Asset>
     */
    public function getUserAssets(int $userId): Collection;
}

