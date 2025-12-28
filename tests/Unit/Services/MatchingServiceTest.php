<?php

use App\Events\OrderMatched;
use App\Models\Order;
use App\Models\Trade;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\TradeRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\MatchingService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
    $this->assetRepository = Mockery::mock(AssetRepositoryInterface::class);
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->tradeRepository = Mockery::mock(TradeRepositoryInterface::class);

    $this->matchingService = new MatchingService(
        $this->orderRepository,
        $this->assetRepository,
        $this->userRepository,
        $this->tradeRepository
    );
});

afterEach(function () {
    Mockery::close();
});

test('matchOrder returns null when no matching order exists', function () {
    $order = Order::factory()->make([
        'side' => Order::SIDE_BUY,
        'symbol' => 'BTC',
        'price' => '50000.00',
    ]);

    $this->orderRepository->shouldReceive('findMatchingSellOrder')
        ->with('BTC', '50000.00')
        ->andReturn(null);

    $result = $this->matchingService->matchOrder($order);

    expect($result)->toBeNull();
});

test('matchOrder executes trade when matching order exists', function () {
    Event::fake();

    $buyer = \App\Models\User::factory()->create();
    $seller = \App\Models\User::factory()->create();

    $buyOrder = Order::factory()->create([
        'user_id' => $buyer->id,
        'side' => Order::SIDE_BUY,
        'symbol' => 'BTC',
        'price' => '50000.00',
        'amount' => '1.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    $sellOrder = Order::factory()->create([
        'user_id' => $seller->id,
        'side' => Order::SIDE_SELL,
        'symbol' => 'BTC',
        'price' => '50000.00',
        'amount' => '1.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    $buyerAsset = \App\Models\Asset::factory()->make([
        'user_id' => $buyer->id,
        'symbol' => 'BTC',
        'amount' => '0.00000000',
    ]);

    $sellerAsset = \App\Models\Asset::factory()->make([
        'user_id' => $seller->id,
        'symbol' => 'BTC',
        'amount' => '1.00000000',
        'locked_amount' => '1.00000000',
    ]);

    $trade = Trade::factory()->make([
        'buy_order_id' => $buyOrder->id,
        'sell_order_id' => $sellOrder->id,
        'price' => '50000.00',
        'amount' => '1.00000000',
        'commission' => '750.00000000', // 1.5% of 50000
    ]);

    $this->orderRepository->shouldReceive('findMatchingSellOrder')
        ->with('BTC', '50000.00')
        ->andReturn($sellOrder);

    $this->orderRepository->shouldReceive('updateStatus')
        ->with($buyOrder, Order::STATUS_FILLED)
        ->once();

    $this->orderRepository->shouldReceive('updateStatus')
        ->with($sellOrder, Order::STATUS_FILLED)
        ->once();

    $this->userRepository->shouldReceive('findById')
        ->with($buyer->id)
        ->andReturn($buyer);

    $this->userRepository->shouldReceive('findById')
        ->with($seller->id)
        ->andReturn($seller);

    $this->assetRepository->shouldReceive('findOrCreateByUserAndSymbol')
        ->with($buyer->id, 'BTC')
        ->andReturn($buyerAsset);

    $this->assetRepository->shouldReceive('addAmount')
        ->with($buyerAsset, '1.00000000')
        ->once();

    $this->assetRepository->shouldReceive('findByUserAndSymbol')
        ->with($seller->id, 'BTC')
        ->andReturn($sellerAsset);

    $this->assetRepository->shouldReceive('unlockAmount')
        ->with($sellerAsset, '1.00000000')
        ->once();

    $this->assetRepository->shouldReceive('subtractAmount')
        ->with($sellerAsset, '1.00000000')
        ->once();

    $this->userRepository->shouldReceive('addBalance')
        ->with($seller, '49250.00000000') // 50000 - 750 commission
        ->once();

    $this->tradeRepository->shouldReceive('create')
        ->once()
        ->andReturn($trade);

    $result = $this->matchingService->matchOrder($buyOrder);

    expect($result)->toBe($trade);
    Event::assertDispatched(OrderMatched::class);
});

test('matchOrder calculates commission correctly', function () {
    Event::fake();

    $buyer = \App\Models\User::factory()->create();
    $seller = \App\Models\User::factory()->create();

    $buyOrder = Order::factory()->create([
        'user_id' => $buyer->id,
        'side' => Order::SIDE_BUY,
        'symbol' => 'BTC',
        'price' => '50000.00',
        'amount' => '2.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    $sellOrder = Order::factory()->create([
        'user_id' => $seller->id,
        'side' => Order::SIDE_SELL,
        'symbol' => 'BTC',
        'price' => '50000.00',
        'amount' => '2.00000000',
        'status' => Order::STATUS_OPEN,
    ]);

    // USD value = 50000 * 2 = 100000
    // Commission = 100000 * 0.015 = 1500
    // Seller receives = 100000 - 1500 = 98500

    $this->orderRepository->shouldReceive('findMatchingSellOrder')
        ->andReturn($sellOrder);

    $this->orderRepository->shouldReceive('updateStatus')
        ->twice();

    $this->userRepository->shouldReceive('findById')
        ->andReturn($buyer, $seller);

    $this->assetRepository->shouldReceive('findOrCreateByUserAndSymbol')
        ->andReturn(\App\Models\Asset::factory()->make());

    $this->assetRepository->shouldReceive('addAmount')
        ->once();

    $sellerAsset = Mockery::mock(\App\Models\Asset::class)->makePartial();
    $sellerAsset->user_id = $seller->id;
    $sellerAsset->symbol = 'BTC';
    $sellerAsset->amount = '2.00000000';
    $sellerAsset->locked_amount = '2.00000000';
    
    $sellerAsset->shouldReceive('lockForUpdate')
        ->andReturnSelf();
    $sellerAsset->shouldReceive('refresh')
        ->andReturnSelf();
    
    $this->assetRepository->shouldReceive('findByUserAndSymbol')
        ->with($seller->id, 'BTC')
        ->andReturn($sellerAsset);

    $this->assetRepository->shouldReceive('unlockAmount')
        ->once();

    $this->assetRepository->shouldReceive('subtractAmount')
        ->once();

    $this->userRepository->shouldReceive('addBalance')
        ->with($seller, '98500.00000000')
        ->once();

    $this->tradeRepository->shouldReceive('create')
        ->with(\Mockery::on(function ($data) {
            return $data['commission'] === '1500.00000000';
        }))
        ->andReturn(Trade::factory()->make());

    $this->matchingService->matchOrder($buyOrder);
});

