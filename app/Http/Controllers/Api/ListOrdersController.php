<?php

namespace App\Http\Controllers\Api;

use App\Actions\Trading\ListOrdersAction;
use App\DTOs\OrderResponseDto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListOrdersController
{
    public function __invoke(Request $request, ListOrdersAction $action): JsonResponse
    {
        $symbol = $request->query('symbol');
        $orders = $action->execute($request->user()->id, $symbol);

        $orderDtos = $orders->map(fn ($order) => new OrderResponseDto(
            id: $order->id,
            symbol: $order->symbol,
            side: $order->side,
            price: (string) $order->price,
            amount: (string) $order->amount,
            status: $order->status,
            createdAt: $order->created_at->toIso8601String(),
            updatedAt: $order->updated_at->toIso8601String(),
        ))->map(fn (OrderResponseDto $dto) => $dto->toArray())->values();

        return response()->json([
            'data' => $orderDtos,
            'message' => 'Success',
            'status_code' => 200,
        ]);
    }
}

