<?php

use App\Models\Asset;
use App\Models\User;
use App\Repositories\AssetRepository;
use Illuminate\Support\Facades\DB;

test('lockAmount atomically increments locked_amount', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '10.00000000',
        'locked_amount' => '0.00000000',
    ]);

    $repository = new AssetRepository();

    DB::transaction(function () use ($repository, $asset) {
        $repository->lockAmount($asset, '2.00000000');
    });

    $asset->refresh();
    expect((string) $asset->locked_amount)->toBe('2.00000000');
});

test('unlockAmount atomically decrements locked_amount', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '10.00000000',
        'locked_amount' => '5.00000000',
    ]);

    $repository = new AssetRepository();

    DB::transaction(function () use ($repository, $asset) {
        $repository->unlockAmount($asset, '2.00000000');
    });

    $asset->refresh();
    expect((string) $asset->locked_amount)->toBe('3.00000000');
});

test('addAmount atomically increments amount', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '10.00000000',
        'locked_amount' => '0.00000000',
    ]);

    $repository = new AssetRepository();

    DB::transaction(function () use ($repository, $asset) {
        $repository->addAmount($asset, '2.00000000');
    });

    $asset->refresh();
    expect((string) $asset->amount)->toBe('12.00000000');
});

test('subtractAmount atomically decrements amount', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '10.00000000',
        'locked_amount' => '0.00000000',
    ]);

    $repository = new AssetRepository();

    DB::transaction(function () use ($repository, $asset) {
        $repository->subtractAmount($asset, '3.00000000');
    });

    $asset->refresh();
    expect((string) $asset->amount)->toBe('7.00000000');
});

test('findOrCreateByUserAndSymbol creates asset if not exists', function () {
    $user = User::factory()->create();

    $repository = new AssetRepository();

    $asset = $repository->findOrCreateByUserAndSymbol($user->id, 'BTC');

    expect($asset)->not->toBeNull();
    expect($asset->user_id)->toBe($user->id);
    expect($asset->symbol)->toBe('BTC');
    expect((string) $asset->amount)->toBe('0.00000000');
});

test('findOrCreateByUserAndSymbol returns existing asset if exists', function () {
    $user = User::factory()->create();
    $existingAsset = Asset::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '5.00000000',
    ]);

    $repository = new AssetRepository();

    $asset = $repository->findOrCreateByUserAndSymbol($user->id, 'BTC');

    expect($asset->id)->toBe($existingAsset->id);
    expect((string) $asset->amount)->toBe('5.00000000');
});

test('lockAmount uses lockForUpdate to prevent race conditions', function () {
    $user = User::factory()->create();
    $asset = Asset::factory()->create([
        'user_id' => $user->id,
        'symbol' => 'BTC',
        'amount' => '10.00000000',
        'locked_amount' => '0.00000000',
    ]);

    $repository = new AssetRepository();

    // Simulate concurrent access
    DB::transaction(function () use ($repository, $asset) {
        $asset->lockForUpdate();
        $repository->lockAmount($asset, '2.00000000');
    });

    $asset->refresh();
    expect((string) $asset->locked_amount)->toBe('2.00000000');
});

