<?php

use App\Models\Order;
use App\Models\User;

test('authenticated user can list their orders', function () {
    $user = User::factory()->create();

    $order1 = Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'side' => 'buy',
        'created_at' => now()->subHour(),
    ]);

    $order2 = Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'ETH',
        'side' => 'sell',
        'created_at' => now(),
    ]);

    // Create order for another user (should not appear)
    Order::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $response = $this->actingAs($user)->getJson('/api/my-orders');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'symbol',
                    'side',
                    'price',
                    'amount',
                    'status',
                ],
            ],
        ]);

    $orders = $response->json('data');
    expect($orders)->toHaveCount(2);
    expect($orders[0]['id'])->toBe($order2->id); // Most recent first
    expect($orders[1]['id'])->toBe($order1->id);
});

test('user can filter orders by symbol', function () {
    $user = User::factory()->create();

    Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
    ]);

    Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'ETH',
    ]);

    $response = $this->actingAs($user)->getJson('/api/my-orders?symbol=BTC');

    $response->assertStatus(200);
    $orders = $response->json('data');
    expect($orders)->toHaveCount(1);
    expect($orders[0]['symbol'])->toBe('BTC');
});

test('list orders returns empty array when user has no orders', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/my-orders');

    $response->assertStatus(200);
    expect($response->json('data'))->toBeArray();
    expect($response->json('data'))->toHaveCount(0);
});

test('unauthenticated user cannot list orders', function () {
    $response = $this->getJson('/api/my-orders');

    $response->assertStatus(401);
});

test('orders are returned in descending order by created_at', function () {
    $user = User::factory()->create();

    $oldOrder = Order::factory()->create([
        'user_id' => $user->id,
        'created_at' => now()->subHour(),
    ]);

    $newOrder = Order::factory()->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->getJson('/api/my-orders');

    $orders = $response->json('data');
    expect($orders[0]['id'])->toBe($newOrder->id);
    expect($orders[1]['id'])->toBe($oldOrder->id);
});

