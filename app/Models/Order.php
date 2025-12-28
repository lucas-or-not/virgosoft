<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;
    public const STATUS_OPEN = 1;
    public const STATUS_FILLED = 2;
    public const STATUS_CANCELLED = 3;

    public const SIDE_BUY = 'buy';
    public const SIDE_SELL = 'sell';

    protected $fillable = [
        'user_id',
        'symbol',
        'side',
        'price',
        'amount',
        'status',
        'locked_usd',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:8',
            'amount' => 'decimal:8',
            'locked_usd' => 'decimal:8',
            'status' => 'integer',
        ];
    }

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include open orders.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope a query to only include filled orders.
     */
    public function scopeFilled($query)
    {
        return $query->where('status', self::STATUS_FILLED);
    }

    /**
     * Scope a query to only include cancelled orders.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    /**
     * Scope a query to only include orders for a specific symbol.
     */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('symbol', $symbol);
    }

    /**
     * Scope a query to only include buy orders.
     */
    public function scopeBuyOrders($query)
    {
        return $query->where('side', self::SIDE_BUY);
    }

    /**
     * Scope a query to only include sell orders.
     */
    public function scopeSellOrders($query)
    {
        return $query->where('side', self::SIDE_SELL);
    }

    /**
     * Scope a query to find matching orders for a new order.
     */
    public function scopeMatchingOrders($query, string $symbol, string $side, string $price)
    {
        $query->where('symbol', $symbol)
            ->where('status', self::STATUS_OPEN);

        if ($side === self::SIDE_BUY) {
            // For buy orders, find sell orders where sell.price <= buy.price
            $query->where('side', self::SIDE_SELL)
                ->where('price', '<=', $price)
                ->orderBy('price', 'asc');
        } else {
            // For sell orders, find buy orders where buy.price >= sell.price
            $query->where('side', self::SIDE_BUY)
                ->where('price', '>=', $price)
                ->orderBy('price', 'desc');
        }

        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Check if the order is a buy order.
     */
    public function isBuy(): bool
    {
        return $this->side === self::SIDE_BUY;
    }

    /**
     * Check if the order is a sell order.
     */
    public function isSell(): bool
    {
        return $this->side === self::SIDE_SELL;
    }

    /**
     * Check if the order is open.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Mark the order as filled.
     */
    public function markAsFilled(): void
    {
        $this->update(['status' => self::STATUS_FILLED]);
    }

    /**
     * Mark the order as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
