<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Trade>
 */
class TradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'buy_order_id' => Order::factory(),
            'sell_order_id' => Order::factory(),
            'symbol' => 'BTC',
            'price' => (string) fake()->randomFloat(2, 10000, 100000),
            'amount' => (string) fake()->randomFloat(8, 0.001, 10),
            'commission' => (string) fake()->randomFloat(8, 1, 1000),
            'buyer_id' => User::factory(),
            'seller_id' => User::factory(),
        ];
    }
}

