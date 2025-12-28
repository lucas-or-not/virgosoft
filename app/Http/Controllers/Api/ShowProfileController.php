<?php

namespace App\Http\Controllers\Api;

use App\Actions\Trading\ShowProfileAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ShowProfileController
{
    public function __invoke(Request $request, ShowProfileAction $action): JsonResponse
    {
        $profile = $action->execute($request->user()->id);

        return response()->json([
            'data' => $profile->toArray(),
            'message' => 'Success',
            'status_code' => 200,
        ]);
    }
}

