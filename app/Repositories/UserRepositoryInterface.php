<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function deductBalance(User $user, string $amount): void;

    public function addBalance(User $user, string $amount): void;

    public function lockBalance(User $user, string $amount): void;

    public function unlockBalance(User $user, string $amount): void;
}

