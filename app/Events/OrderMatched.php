<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $buyOrder,
        public readonly Order $sellOrder,
        public readonly ?Trade $trade = null,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Private channels for the users involved in the trade
            new PrivateChannel('user.'.$this->buyOrder->user_id),
            new PrivateChannel('user.'.$this->sellOrder->user_id),
            // Public channel for orderbook updates (all users can see this)
            new Channel('orderbook.'.$this->buyOrder->symbol),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'buy_order' => [
                'id' => $this->buyOrder->id,
                'symbol' => $this->buyOrder->symbol,
                'side' => $this->buyOrder->side,
                'price' => (string) $this->buyOrder->price,
                'amount' => (string) $this->buyOrder->amount,
                'status' => $this->buyOrder->status,
            ],
            'sell_order' => [
                'id' => $this->sellOrder->id,
                'symbol' => $this->sellOrder->symbol,
                'side' => $this->sellOrder->side,
                'price' => (string) $this->sellOrder->price,
                'amount' => (string) $this->sellOrder->amount,
                'status' => $this->sellOrder->status,
            ],
            'trade' => $this->trade ? [
                'id' => $this->trade->id,
                'symbol' => $this->trade->symbol,
                'price' => (string) $this->trade->price,
                'amount' => (string) $this->trade->amount,
                'commission' => (string) $this->trade->commission,
            ] : null,
        ];
    }
}

