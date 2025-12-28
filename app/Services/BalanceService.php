<?php

namespace App\Services;

use App\DTOs\AssetBalanceDto;
use App\DTOs\ProfileResponseDto;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\UserRepositoryInterface;

final class BalanceService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AssetRepositoryInterface $assetRepository,
    ) {
    }

    public function getUserProfile(int $userId): ProfileResponseDto
    {
        $user = $this->userRepository->findById($userId);

        throw_if($user === null, \RuntimeException::class, 'User not found');

        $assets = $this->assetRepository->getUserAssets($userId);

        $assetDtos = $assets->map(function ($asset) {
            return new AssetBalanceDto(
                symbol: $asset->symbol,
                amount: (string) $asset->amount,
                lockedAmount: (string) $asset->locked_amount,
                availableAmount: (string) $asset->available_amount,
            );
        })->toArray();

        return new ProfileResponseDto(
            balance: (string) $user->balance,
            assets: $assetDtos,
        );
    }

    public function addBalance(int $userId, string $amount): void
    {
        $user = $this->userRepository->findById($userId);
        throw_if($user === null, \RuntimeException::class, 'User not found');

        $this->userRepository->addBalance($user, $amount);
    }

    public function addAsset(int $userId, string $symbol, string $amount): void
    {
        $asset = $this->assetRepository->findOrCreateByUserAndSymbol($userId, $symbol);
        $this->assetRepository->addAmount($asset, $amount);
    }
}

