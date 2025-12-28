<?php

namespace App\DTOs;

final class AssetBalanceDto
{
    public function __construct(
        public readonly string $symbol,
        public readonly string $amount,
        public readonly string $lockedAmount,
        public readonly string $availableAmount,
    ) {
    }

    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'amount' => $this->amount,
            'locked_amount' => $this->lockedAmount,
            'available_amount' => $this->availableAmount,
        ];
    }
}

