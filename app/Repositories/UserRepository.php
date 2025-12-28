<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function deductBalance(User $user, string $amount): void
    {
        $user->decrement('balance', $amount);
    }

    public function addBalance(User $user, string $amount): void
    {
        $user->increment('balance', $amount);
    }

    public function lockBalance(User $user, string $amount): void
    {
        $user->decrement('balance', $amount);
    }

    public function unlockBalance(User $user, string $amount): void
    {
        $user->increment('balance', $amount);
    }
}

