<?php

namespace App\Policies;

use App\Models\Rate;
use App\Models\User;

class RatePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function view(User $user, Rate $rate): bool
    {
        return $user->id === $rate->entertainer->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function update(User $user, Rate $rate): bool
    {
        return $user->id === $rate->entertainer->user_id;
    }

    public function delete(User $user, Rate $rate): bool
    {
        return $user->id === $rate->entertainer->user_id;
    }
}
