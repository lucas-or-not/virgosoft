<?php

use App\Models\User;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\App;

test('authenticated user can get profile', function () {
    $user = User::factory()->create([
        'balance' => '10000.00000000',
    ]);

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'balance',
                'assets' => [
                    '*' => [
                        'symbol',
                        'amount',
                        'locked_amount',
                        'available_amount',
                    ],
                ],
            ],
        ]);

    expect($response->json('data.balance'))->toBe('10000.00000000');
});

test('unauthenticated user cannot get profile', function () {
    $response = $this->getJson('/api/profile');

    $response->assertStatus(401);
});

test('profile returns user assets', function () {
    $user = User::factory()->create([
        'balance' => '5000.00000000',
    ]);

    // Create an asset for the user
    $assetRepository = App::make(AssetRepositoryInterface::class);
    $asset = $assetRepository->findOrCreateByUserAndSymbol($user->id, 'BTC');
    $assetRepository->addAmount($asset, '1.50000000');

    $response = $this->actingAs($user)->getJson('/api/profile');

    $response->assertStatus(200);
    $assets = $response->json('data.assets');
    
    expect($assets)->toBeArray();
    expect($assets)->toHaveCount(1);
    expect($assets[0]['symbol'])->toBe('BTC');
    expect($assets[0]['amount'])->toBe('1.50000000');
});

