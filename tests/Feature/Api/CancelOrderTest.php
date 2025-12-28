<?php

use App\Models\Order;
use App\Models\User;
use App\Repositories\AssetRepositoryInterface;
use Illuminate\Support\Facades\App;

test('authenticated user can cancel their open order', function () {
    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    // Create an order
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'side' => 'buy',
        'status' => Order::STATUS_OPEN,
        'price' => '50000.00',
        'amount' => '1.00',
    ]);

    $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(200);

    // Verify order was cancelled
    $order->refresh();
    expect($order->status)->toBe(Order::STATUS_CANCELLED);
});

test('user cannot cancel filled order', function () {
    $user = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Order::STATUS_FILLED,
    ]);

    $response = $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('cancel');
});

test('user cannot cancel other user order', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $order = Order::factory()->create([
        'user_id' => $user1->id,
        'status' => Order::STATUS_OPEN,
    ]);

    $response = $this->actingAs($user2)->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(500); // Or 403, depending on your error handling
});

test('cancelling buy order unlocks balance', function () {
    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'side' => 'buy',
        'status' => Order::STATUS_OPEN,
        'locked_usd' => '50000.00000000',
    ]);

    // Lock the balance (simulate order creation)
    $userRepository = App::make(\App\Repositories\UserRepositoryInterface::class);
    $userRepository->lockBalance($user, '50000.00000000');
    
    $initialBalance = (string) $user->fresh()->balance;
    expect($initialBalance)->toBe('50000.00000000'); // 100000 - 50000

    $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    // Balance should be restored
    $finalBalance = (string) $user->fresh()->balance;
    expect($finalBalance)->toBe('100000.00000000');
});

test('cancelling sell order unlocks asset', function () {
    $user = User::factory()->create();

    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($user->id, 'BTC');
    $assetRepository->addAmount($asset, '2.00000000');
    $assetRepository->lockAmount($asset, '1.00000000');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'side' => 'sell',
        'status' => Order::STATUS_OPEN,
        'amount' => '1.00000000',
    ]);

    $this->actingAs($user)->postJson("/api/orders/{$order->id}/cancel");

    // Asset should be unlocked
    $asset->refresh();
    expect((string) $asset->locked_amount)->toBe('0.00000000');
});

test('unauthenticated user cannot cancel order', function () {
    $order = Order::factory()->create([
        'status' => Order::STATUS_OPEN,
    ]);

    $response = $this->postJson("/api/orders/{$order->id}/cancel");

    $response->assertStatus(401);
});

