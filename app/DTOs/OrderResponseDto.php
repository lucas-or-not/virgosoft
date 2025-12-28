<?php

namespace App\DTOs;

final class OrderResponseDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $symbol,
        public readonly string $side,
        public readonly string $price,
        public readonly string $amount,
        public readonly int $status,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'side' => $this->side,
            'price' => $this->price,
            'amount' => $this->amount,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

