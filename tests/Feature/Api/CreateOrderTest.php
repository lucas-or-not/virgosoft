<?php

use App\Models\Order;
use App\Models\User;
use App\Repositories\AssetRepositoryInterface;
use Illuminate\Support\Facades\App;

test('authenticated user can create buy order', function () {
    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => [
                'id',
                'symbol',
                'side',
                'price',
                'amount',
                'status',
            ],
        ]);

    expect($response->json('data.side'))->toBe('buy');
    expect($response->json('data.symbol'))->toBe('BTC');
    expect($response->json('data.price'))->toBe('50000.00000000');
    expect($response->json('data.amount'))->toBe('1.00000000');

    // Verify order was created in database
    $this->assertDatabaseHas('orders', [
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'side' => 'buy',
        'status' => Order::STATUS_OPEN,
    ]);
});

test('authenticated user can create sell order', function () {
    $user = User::factory()->create();

    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($user->id, 'BTC');
    $assetRepository->addAmount($asset, '2.00000000');

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '50000.00',
        'amount' => '1.00',
    ]);

    $response->assertStatus(201);
    expect($response->json('data.side'))->toBe('sell');
});

test('user cannot create buy order with insufficient balance', function () {
    $user = User::factory()->create([
        'balance' => '1000.00000000', // Not enough for 1 BTC at $50000
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00',
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('balance');
});

test('user cannot create sell order with insufficient asset', function () {
    $user = User::factory()->create();

    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($user->id, 'BTC');
    $assetRepository->addAmount($asset, '0.50000000'); // Only 0.5 BTC

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '50000.00',
        'amount' => '1.00', // Trying to sell 1 BTC
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('asset');
});

test('order creation validates required fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/orders', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
});

test('order creation validates symbol', function () {
    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'INVALID',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['symbol']);
});

test('order creation validates side', function () {
    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'invalid',
        'price' => '50000.00',
        'amount' => '1.00',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['side']);
});

test('order creation validates price is positive', function () {
    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '-100.00',
        'amount' => '1.00',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
});

test('order creation validates amount is positive', function () {
    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $response = $this->actingAs($user)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '0',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['amount']);
});

