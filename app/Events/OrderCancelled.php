<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderCancelled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
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
            // Public channel for orderbook updates (all users can see this)
            new Channel('orderbook.'.$this->order->symbol),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.cancelled';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'symbol' => $this->order->symbol,
                'side' => $this->order->side,
                'price' => (string) $this->order->price,
                'amount' => (string) $this->order->amount,
                'status' => $this->order->status,
            ],
        ];
    }
}

