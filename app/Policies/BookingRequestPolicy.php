<?php

namespace App\Policies;

use App\Models\BookingRequest;
use App\Models\User;

class BookingRequestPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'entertainer']);
    }

    public function view(User $user, BookingRequest $bookingRequest): bool
    {
        return $user->id === $bookingRequest->entertainer->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, BookingRequest $bookingRequest): bool
    {
        return $user->id === $bookingRequest->entertainer->user_id;
    }

    public function delete(User $user, BookingRequest $bookingRequest): bool
    {
        return false;
    }
}
