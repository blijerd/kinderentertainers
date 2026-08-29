<?php

namespace App\Actions;

use App\Enums\BookingRequestEventType;
use App\Models\BookingRequest;
use App\Models\BookingRequestEvent;
use App\Models\User;

class RecordBookingRequestEvent
{
    public function handle(
        BookingRequest $bookingRequest,
        BookingRequestEventType $type,
        string $body,
        string $actorType,
        string $actorName,
        bool $visibleToEntertainer = true,
        bool $visibleToCustomer = false,
        ?User $user = null,
    ): BookingRequestEvent {
        return $bookingRequest->events()->create([
            'type' => $type,
            'actor_type' => $actorType,
            'actor_name' => $actorName,
            'body' => $body,
            'visible_to_entertainer' => $visibleToEntertainer,
            'visible_to_customer' => $visibleToCustomer,
            'user_id' => $user?->id,
        ]);
    }
}
