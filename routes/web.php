<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/trading/order', function () {
        return Inertia::render('Trading/OrderForm');
    })->name('trading.order');

    Route::get('/trading/deposit', function () {
        return Inertia::render('Trading/Deposit');
    })->name('trading.deposit');
});

require __DIR__.'/settings.php';
