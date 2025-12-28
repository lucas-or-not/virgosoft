<?php

namespace App\Http\Controllers\Api;

use App\Actions\Trading\CancelOrderAction;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelOrderController
{
    public function __invoke(Order $order, Request $request, CancelOrderAction $action): JsonResponse
    {
        $action->execute($order->id, $request->user()->id);

        return response()->json([
            'data' => null,
            'message' => 'Order cancelled successfully',
            'status_code' => 200,
        ]);
    }
}

