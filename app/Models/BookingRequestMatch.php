<?php

namespace App\Models;

use App\Enums\BookingRequestMatchStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_request_id', 'entertainer_id', 'status', 'matched_at', 'responded_at'])]
class BookingRequestMatch extends Model
{
    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class);
    }

    protected function casts(): array
    {
        return [
            'status' => BookingRequestMatchStatus::class,
            'matched_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }
}
