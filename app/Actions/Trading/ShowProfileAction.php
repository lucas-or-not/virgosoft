<?php

namespace App\Actions\Trading;

use App\DTOs\ProfileResponseDto;
use App\Services\BalanceService;

final readonly class ShowProfileAction
{
    public function __construct(
        private BalanceService $balanceService,
    ) {
    }

    public function execute(int $userId): ProfileResponseDto
    {
        return $this->balanceService->getUserProfile($userId);
    }
}

