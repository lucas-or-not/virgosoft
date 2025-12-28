<?php

use App\DTOs\CreateOrderDto;
use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Exceptions\InsufficientAssetException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderNotCancellableException;
use App\Models\Order;
use App\Models\User;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\MatchingServiceInterface;
use App\Services\OrderService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
    $this->assetRepository = Mockery::mock(AssetRepositoryInterface::class);
    $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
    $this->matchingService = Mockery::mock(MatchingServiceInterface::class);

    $this->orderService = new OrderService(
        $this->orderRepository,
        $this->assetRepository,
        $this->userRepository,
        $this->matchingService
    );
});

afterEach(function () {
    Mockery::close();
});

test('createOrder validates and locks balance for buy order', function () {
    Event::fake();

    $user = User::factory()->create([
        'balance' => '100000.00000000',
    ]);

    $dto = new CreateOrderDto(
        userId: $user->id,
        symbol: 'BTC',
        side: Order::SIDE_BUY,
        price: '50000.00',
        amount: '1.00'
    );

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Order::STATUS_OPEN,
    ]);

    $this->userRepository->shouldReceive('findById')
        ->with($user->id)
        ->andReturn($user);

    $this->userRepository->shouldReceive('lockBalance')
        ->once()
        ->with($user, '50000.00000000');

    $this->orderRepository->shouldReceive('create')
        ->once()
        ->andReturn($order);

    $this->matchingService->shouldReceive('matchOrder')
        ->once()
        ->with($order)
        ->andReturn(null);

    $result = $this->orderService->createOrder($dto);

    expect($result)->toBe($order);
    Event::assertDispatched(OrderCreated::class);
});

test('createOrder throws InsufficientBalanceException for buy order with insufficient balance', function () {
    $user = User::factory()->create([
        'balance' => '1000.00000000', // Not enough
    ]);

    $dto = new CreateOrderDto(
        userId: $user->id,
        symbol: 'BTC',
        side: Order::SIDE_BUY,
        price: '50000.00',
        amount: '1.00'
    );

    $this->userRepository->shouldReceive('findById')
        ->with($user->id)
        ->andReturn($user);

    expect(fn () => $this->orderService->createOrder($dto))
        ->toThrow(InsufficientBalanceException::class);
});

test('createOrder validates and locks asset for sell order', function () {
    Event::fake();

    $user = User::factory()->create();
    $asset = \App\Models\Asset::factory()->make([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '2.00000000',
        'locked_amount' => '0.00000000',
    ]);

    $dto = new CreateOrderDto(
        userId: $user->id,
        symbol: 'BTC',
        side: Order::SIDE_SELL,
        price: '50000.00',
        amount: '1.00'
    );

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Order::STATUS_OPEN,
    ]);

    $this->assetRepository->shouldReceive('findOrCreateByUserAndSymbol')
        ->with($user->id, 'BTC')
        ->andReturn($asset);

    $this->assetRepository->shouldReceive('lockAmount')
        ->once()
        ->with($asset, '1.00');

    $this->orderRepository->shouldReceive('create')
        ->once()
        ->andReturn($order);

    $this->matchingService->shouldReceive('matchOrder')
        ->once()
        ->with($order)
        ->andReturn(null);

    $result = $this->orderService->createOrder($dto);

    expect($result)->toBe($order);
    Event::assertDispatched(OrderCreated::class);
});

test('createOrder throws InsufficientAssetException for sell order with insufficient asset', function () {
    $user = User::factory()->create();
    $asset = \App\Models\Asset::factory()->make([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '0.50000000',
        'locked_amount' => '0.00000000',
    ]);

    $dto = new CreateOrderDto(
        userId: $user->id,
        symbol: 'BTC',
        side: Order::SIDE_SELL,
        price: '50000.00',
        amount: '1.00'
    );

    $this->assetRepository->shouldReceive('findOrCreateByUserAndSymbol')
        ->with($user->id, 'BTC')
        ->andReturn($asset);

    expect(fn () => $this->orderService->createOrder($dto))
        ->toThrow(InsufficientAssetException::class);
});

test('cancelOrder unlocks balance for buy order', function () {
    Event::fake();

    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'side' => Order::SIDE_BUY,
        'status' => Order::STATUS_OPEN,
        'locked_usd' => '50000.00000000',
    ]);

    $this->orderRepository->shouldReceive('findByIdOrFail')
        ->with($order->id)
        ->andReturn($order);

    $this->userRepository->shouldReceive('findById')
        ->with($user->id)
        ->andReturn($user);

    $this->userRepository->shouldReceive('unlockBalance')
        ->once()
        ->with($user, '50000.00000000');

    $this->orderRepository->shouldReceive('updateStatus')
        ->once()
        ->with($order, Order::STATUS_CANCELLED);

    $this->orderService->cancelOrder($order->id, $user->id);

    Event::assertDispatched(OrderCancelled::class);
});

test('cancelOrder throws OrderNotCancellableException for filled order', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Order::STATUS_FILLED,
    ]);

    $this->orderRepository->shouldReceive('findByIdOrFail')
        ->with($order->id)
        ->andReturn($order);

    expect(fn () => $this->orderService->cancelOrder($order->id, $user->id))
        ->toThrow(OrderNotCancellableException::class);
});

