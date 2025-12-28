<?php

namespace App\Http\Controllers\Api;

use App\Actions\Trading\CreateOrderAction;
use App\DTOs\OrderResponseDto;
use App\Http\Requests\CreateOrderRequest;
use Illuminate\Http\JsonResponse;

final class CreateOrderController
{
    public function __invoke(CreateOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $dto = $request->toDto();
        $order = $action->execute($dto);

        $orderDto = new OrderResponseDto(
            id: $order->id,
            symbol: $order->symbol,
            side: $order->side,
            price: (string) $order->price,
            amount: (string) $order->amount,
            status: $order->status,
            createdAt: $order->created_at->toIso8601String(),
            updatedAt: $order->updated_at->toIso8601String(),
        );

        return response()->json([
            'data' => $orderDto->toArray(),
            'message' => 'Order created successfully',
            'status_code' => 201,
        ], 201);
    }
}

