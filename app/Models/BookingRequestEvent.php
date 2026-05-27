<?php

namespace App\Models;

use App\Enums\BookingRequestEventType;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'booking_request_id',
    'user_id',
    'type',
    'actor_type',
    'actor_name',
    'body',
    'old_status',
    'new_status',
    'visible_to_entertainer',
    'visible_to_customer',
])]
class BookingRequestEvent extends Model
{
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'type' => BookingRequestEventType::class,
            'old_status' => BookingStatus::class,
            'new_status' => BookingStatus::class,
            'visible_to_entertainer' => 'boolean',
            'visible_to_customer' => 'boolean',
        ];
    }
}
