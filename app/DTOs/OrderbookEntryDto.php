<?php

namespace App\DTOs;

final class OrderbookEntryDto
{
    public function __construct(
        public readonly string $price,
        public readonly string $amount,
        public readonly string $side,
        public readonly ?string $symbol = null,
    ) {
    }

    public function toArray(): array
    {
        $data = [
            'price' => $this->price,
            'amount' => $this->amount,
            'side' => $this->side,
        ];

        if ($this->symbol !== null) {
            $data['symbol'] = $this->symbol;
        }

        return $data;
    }
}

