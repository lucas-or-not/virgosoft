<?php

namespace App\Http\Requests;

use App\DTOs\DepositDto;
use Illuminate\Foundation\Http\FormRequest;

final class DepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:usd,asset'],
            'amount' => ['required', 'numeric', 'min:0.00000001'],
            'symbol' => ['required_if:type,asset', 'string', 'in:BTC,ETH'],
        ];
    }

    public function toDto(): DepositDto
    {
        return DepositDto::fromArray([
            'userId' => $this->user()->id,
            'type' => $this->validated('type'),
            'amount' => (string) $this->validated('amount'),
            'symbol' => $this->validated('symbol'),
        ]);
    }
}

