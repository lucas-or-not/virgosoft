<?php

use App\Models\Order;
use App\Models\User;

test('authenticated user can get orderbook for symbol', function () {
    $user = User::factory()->create();

    // Create buy orders
    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00',
        'status' => Order::STATUS_OPEN,
    ]);

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '51000.00',
        'amount' => '0.50',
        'status' => Order::STATUS_OPEN,
    ]);

    // Create sell orders
    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '52000.00',
        'amount' => '2.00',
        'status' => Order::STATUS_OPEN,
    ]);

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '53000.00',
        'amount' => '1.50',
        'status' => Order::STATUS_OPEN,
    ]);

    // Create filled order (should not appear)
    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'status' => Order::STATUS_FILLED,
    ]);

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'buy' => [
                    '*' => [
                        'price',
                        'amount',
                        'side',
                    ],
                ],
                'sell' => [
                    '*' => [
                        'price',
                        'amount',
                        'side',
                    ],
                ],
            ],
        ]);

    $orderbook = $response->json('data');
    expect($orderbook['buy'])->toHaveCount(2);
    expect($orderbook['sell'])->toHaveCount(2);
});

test('orderbook returns buy orders sorted by price descending', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'status' => Order::STATUS_OPEN,
    ]);

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '51000.00',
        'status' => Order::STATUS_OPEN,
    ]);

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

    $buyOrders = $response->json('data.buy');
    expect($buyOrders[0]['price'])->toBe('51000.00000000');
    expect($buyOrders[1]['price'])->toBe('50000.00000000');
});

test('orderbook returns sell orders sorted by price ascending', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '53000.00',
        'status' => Order::STATUS_OPEN,
    ]);

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '52000.00',
        'status' => Order::STATUS_OPEN,
    ]);

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

    $sellOrders = $response->json('data.sell');
    expect($sellOrders[0]['price'])->toBe('52000.00000000');
    expect($sellOrders[1]['price'])->toBe('53000.00000000');
});

test('orderbook only includes open orders', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'status' => Order::STATUS_OPEN,
    ]);

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'status' => Order::STATUS_FILLED,
    ]);

    Order::factory()->create([
        'symbol' => 'BTC',
        'side' => 'buy',
        'status' => Order::STATUS_CANCELLED,
    ]);

    $response = $this->actingAs($user)->getJson('/api/orders?symbol=BTC');

    $buyOrders = $response->json('data.buy');
    expect($buyOrders)->toHaveCount(1);
});

test('orderbook can return all symbols when symbol is not provided', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/orders');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'buy' => [],
                'sell' => [],
            ],
        ]);
});

test('unauthenticated user cannot get orderbook', function () {
    $response = $this->getJson('/api/orders?symbol=BTC');

    $response->assertStatus(401);
});

