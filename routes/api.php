<?php

use App\Http\Controllers\Api\CancelOrderController;
use App\Http\Controllers\Api\CreateOrderController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\GetOrderbookController;
use App\Http\Controllers\Api\ListOrdersController;
use App\Http\Controllers\Api\ShowProfileController;
use Illuminate\Support\Facades\Route;

// Use web middleware for session-based auth (works with Inertia)
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/profile', ShowProfileController::class)->name('api.profile');
    Route::get('/orders', GetOrderbookController::class)->name('api.orders.index');
    Route::get('/my-orders', ListOrdersController::class)->name('api.my-orders.index');
    Route::post('/orders', CreateOrderController::class)->name('api.orders.store');
    Route::post('/orders/{order}/cancel', CancelOrderController::class)->name('api.orders.cancel');
    Route::post('/deposit', DepositController::class)->name('api.deposit');
});

