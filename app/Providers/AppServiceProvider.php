<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderMatched;
use App\Listeners\InvalidateOrderbookCache;
use App\Repositories\AssetRepository;
use App\Repositories\AssetRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\OrderRepositoryInterface;
use App\Repositories\TradeRepository;
use App\Repositories\TradeRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Services\MatchingService;
use App\Services\MatchingServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AssetRepositoryInterface::class, AssetRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TradeRepositoryInterface::class, TradeRepository::class);
        $this->app->bind(MatchingServiceInterface::class, MatchingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen([
            OrderCreated::class,
            OrderMatched::class,
            OrderCancelled::class,
        ], InvalidateOrderbookCache::class);
    }
}
