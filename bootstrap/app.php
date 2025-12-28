<?php

use App\Exceptions\InsufficientAssetException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\OrderNotCancellableException;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/api/deposit',
            '/api/orders',
            '/api/orders/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InsufficientBalanceException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'status_code' => 422,
            ], 422);
        });

        $exceptions->render(function (InsufficientAssetException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'status_code' => 422,
            ], 422);
        });

        $exceptions->render(function (OrderNotCancellableException $e, $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'status_code' => 422,
            ], 422);
        });
    })->create();
