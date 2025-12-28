<?php

use App\Events\OrderMatched;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Repositories\AssetRepositoryInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\App;

test('buy order matches with sell order at same price', function () {
    Event::fake();

    $buyer = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $seller = User::factory()->create();
    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($seller->id, 'BTC');
    $assetRepository->addAmount($asset, '1.00000000');
    $assetRepository->lockAmount($asset, '1.00000000');

    // Create sell order first
    $sellOrder = Order::factory()->create([
        'user_id' => $seller->id,
        'symbol' => 'BTC',
        'side' => Order::SIDE_SELL,
        'price' => '50000.00',
        'amount' => '1.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    // Create buy order (should match immediately)
    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00000000',
    ]);

    $response->assertStatus(201);
    
    // Both orders should be filled
    $buyOrder = Order::find($response->json('data.id'));
    expect($buyOrder->status)->toBe(Order::STATUS_FILLED);
    
    $sellOrder->refresh();
    expect($sellOrder->status)->toBe(Order::STATUS_FILLED);

    // Trade should be created
    $trade = Trade::where('buy_order_id', $buyOrder->id)
        ->where('sell_order_id', $sellOrder->id)
        ->first();
    
    expect($trade)->not->toBeNull();
    expect((string) $trade->price)->toBe('50000.00000000');
    expect((string) $trade->amount)->toBe('1.00000000');

    // Commission should be calculated (1.5% of 50000 = 750)
    $expectedCommission = bcmul('50000.00', '0.015', 8);
    expect((string) $trade->commission)->toBe($expectedCommission);

    // Buyer should receive BTC
    $buyerAsset = $assetRepository->findByUserAndSymbol($buyer->id, 'BTC');
    expect($buyerAsset)->not->toBeNull();
    expect((string) $buyerAsset->amount)->toBe('1.00000000');

    // Seller should receive USD minus commission
    $seller->refresh();
    $expectedSellerUsd = bcsub('50000.00', $expectedCommission, 8);
    expect((string) $seller->balance)->toBe($expectedSellerUsd);

    // Buyer's USD should be deducted
    $buyer->refresh();
    expect((string) $buyer->balance)->toBe('50000.00000000'); // 100000 - 50000

    // Event should be broadcast
    Event::assertDispatched(OrderMatched::class);
});

test('buy order matches with sell order at lower price', function () {
    Event::fake();

    $buyer = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $seller = User::factory()->create();
    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($seller->id, 'BTC');
    $assetRepository->addAmount($asset, '1.00000000');
    $assetRepository->lockAmount($asset, '1.00000000');

    // Create sell order at lower price
    $sellOrder = Order::factory()->create([
        'user_id' => $seller->id,
        'symbol' => 'BTC',
        'side' => Order::SIDE_SELL,
        'price' => '49000.00', // Lower than buy price
        'amount' => '1.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    // Create buy order at higher price (should match at seller's price)
    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00000000',
    ]);

    $response->assertStatus(201);
    
    $buyOrder = Order::find($response->json('data.id'));
    expect($buyOrder->status)->toBe(Order::STATUS_FILLED);
    
    $sellOrder->refresh();
    expect($sellOrder->status)->toBe(Order::STATUS_FILLED);

    // Trade should use seller's price
    $trade = Trade::where('buy_order_id', $buyOrder->id)
        ->where('sell_order_id', $sellOrder->id)
        ->first();
    
    expect((string) $trade->price)->toBe('49000.00000000'); // Seller's price
});

test('sell order matches with buy order at same price', function () {
    Event::fake();

    $buyer = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $seller = User::factory()->create();
    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($seller->id, 'BTC');
    $assetRepository->addAmount($asset, '1.00000000');

    // Create buy order first
    $buyOrder = Order::factory()->create([
        'user_id' => $buyer->id,
        'symbol' => 'BTC',
        'side' => Order::SIDE_BUY,
        'price' => '50000.00',
        'amount' => '1.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    // Create sell order (should match immediately)
    $response = $this->actingAs($seller)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'sell',
        'price' => '50000.00',
        'amount' => '1.00000000',
    ]);

    $response->assertStatus(201);
    
    $sellOrder = Order::find($response->json('data.id'));
    expect($sellOrder->status)->toBe(Order::STATUS_FILLED);
    
    $buyOrder->refresh();
    expect($buyOrder->status)->toBe(Order::STATUS_FILLED);
});

test('order does not match if no matching order exists', function () {
    Event::fake();

    $buyer = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    // Create buy order (no matching sell order)
    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00000000',
    ]);

    $response->assertStatus(201);
    
    $buyOrder = Order::find($response->json('data.id'));
    expect($buyOrder->status)->toBe(Order::STATUS_OPEN); // Should remain open
});

test('order does not match if prices do not overlap', function () {
    Event::fake();

    $buyer = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $seller = User::factory()->create();
    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($seller->id, 'BTC');
    $assetRepository->addAmount($asset, '1.00000000');

    // Create sell order at higher price
    Order::factory()->create([
        'user_id' => $seller->id,
        'symbol' => 'BTC',
        'side' => Order::SIDE_SELL,
        'price' => '51000.00', // Higher than buy price
        'amount' => '1.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    // Create buy order at lower price (should not match)
    $response = $this->actingAs($buyer)->postJson('/api/orders', [
        'symbol' => 'BTC',
        'side' => 'buy',
        'price' => '50000.00',
        'amount' => '1.00000000',
    ]);

    $response->assertStatus(201);
    
    $buyOrder = Order::find($response->json('data.id'));
    expect($buyOrder->status)->toBe(Order::STATUS_OPEN); // Should remain open
});

