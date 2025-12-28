<?php

namespace App\DTOs;

final class DepositDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $type,
        public readonly string $amount,
        public readonly ?string $symbol = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['userId'] ?? $data['user_id'] ?? throw new \InvalidArgumentException('userId is required'),
            type: $data['type'] ?? throw new \InvalidArgumentException('type is required'),
            amount: $data['amount'] ?? throw new \InvalidArgumentException('amount is required'),
            symbol: $data['symbol'] ?? null,
        );
    }
}

