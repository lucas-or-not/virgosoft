<?php

namespace App\DTOs;

final class CreateOrderDto
{
    public function __construct(
        public readonly int $userId,
        public readonly string $symbol,
        public readonly string $side,
        public readonly string $price,
        public readonly string $amount,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['userId'] ?? $data['user_id'] ?? throw new \InvalidArgumentException('userId is required'),
            symbol: $data['symbol'] ?? throw new \InvalidArgumentException('symbol is required'),
            side: $data['side'] ?? throw new \InvalidArgumentException('side is required'),
            price: $data['price'] ?? throw new \InvalidArgumentException('price is required'),
            amount: $data['amount'] ?? throw new \InvalidArgumentException('amount is required'),
        );
    }
}

