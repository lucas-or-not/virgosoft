<?php

namespace App\Http\Controllers\Api;

use App\Actions\Trading\GetOrderbookAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetOrderbookController
{
    public function __invoke(Request $request, GetOrderbookAction $action): JsonResponse
    {
        $request->validate([
            'symbol' => ['nullable', 'string', 'in:BTC,ETH'],
        ]);

        $symbol = $request->query('symbol');
        $orderbook = $action->execute($symbol);

        return response()->json([
            'data' => $orderbook,
            'message' => 'Success',
            'status_code' => 200,
        ]);
    }
}

