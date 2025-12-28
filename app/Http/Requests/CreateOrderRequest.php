<?php

namespace App\Http\Requests;

use App\DTOs\CreateOrderDto;
use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', 'in:BTC,ETH'],
            'side' => ['required', 'string', 'in:buy,sell'],
            'price' => ['required', 'numeric', 'min:0.00000001'],
            'amount' => ['required', 'numeric', 'min:0.00000001'],
        ];
    }

    public function toDto(): CreateOrderDto
    {
        return CreateOrderDto::fromArray([
            'userId' => $this->user()->id,
            'symbol' => $this->validated('symbol'),
            'side' => $this->validated('side'),
            'price' => (string) $this->validated('price'),
            'amount' => (string) $this->validated('amount'),
        ]);
    }
}

