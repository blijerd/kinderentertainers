<?php

namespace App\Policies;

use App\Models\Entertainer;
use App\Models\User;

class EntertainerPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function view(User $user, Entertainer $entertainer): bool
    {
        return $user->id === $entertainer->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Entertainer $entertainer): bool
    {
        return $user->id === $entertainer->user_id;
    }

    public function delete(User $user, Entertainer $entertainer): bool
    {
        return false;
    }
}
