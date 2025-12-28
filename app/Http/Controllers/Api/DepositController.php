<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\DepositRequest;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class DepositController
{
    public function __construct(
        private readonly BalanceService $balanceService,
    ) {
    }

    public function __invoke(DepositRequest $request): JsonResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($dto, &$message) {
            if ($dto->type === 'usd') {
                $this->balanceService->addBalance($dto->userId, $dto->amount);
                $message = 'USD balance added successfully';
            } else {
                $this->balanceService->addAsset($dto->userId, $dto->symbol, $dto->amount);
                $message = $dto->symbol.' added successfully';
            }
        });

        return response()->json([
            'data' => null,
            'message' => $message,
            'status_code' => 200,
        ]);
    }
}

