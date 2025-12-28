<?php

namespace App\DTOs;

final class ProfileResponseDto
{
    /**
     * @param  array<AssetBalanceDto>  $assets
     */
    public function __construct(
        public readonly string $balance,
        public readonly array $assets,
    ) {
    }

    public function toArray(): array
    {
        return [
            'balance' => $this->balance,
            'assets' => array_map(fn (AssetBalanceDto $asset) => $asset->toArray(), $this->assets),
        ];
    }
}

