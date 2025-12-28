<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'symbol',
        'amount',
        'locked_amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'locked_amount' => 'decimal:8',
        ];
    }

    /**
     * Get the user that owns the asset.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include assets for a specific symbol.
     */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Get the available amount (amount - locked_amount).
     */
    public function getAvailableAmountAttribute(): string
    {
        return (string) ($this->amount - $this->locked_amount);
    }

    /**
     * Lock an amount of the asset.
     */
    public function lockAmount(string $amount): void
    {
        $this->increment('locked_amount', $amount);
    }

    /**
     * Unlock an amount of the asset.
     */
    public function unlockAmount(string $amount): void
    {
        $this->decrement('locked_amount', $amount);
    }

    /**
     * Add an amount to the asset.
     */
    public function addAmount(string $amount): void
    {
        $this->increment('amount', $amount);
    }

    /**
     * Subtract an amount from the asset.
     */
    public function subtractAmount(string $amount): void
    {
        $this->decrement('amount', $amount);
    }
}
