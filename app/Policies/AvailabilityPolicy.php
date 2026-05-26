<?php

namespace App\Policies;

use App\Models\Availability;
use App\Models\User;

class AvailabilityPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function view(User $user, Availability $availability): bool
    {
        return $user->id === $availability->entertainer->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function update(User $user, Availability $availability): bool
    {
        return $user->id === $availability->entertainer->user_id;
    }

    public function delete(User $user, Availability $availability): bool
    {
        return $user->id === $availability->entertainer->user_id;
    }
}
