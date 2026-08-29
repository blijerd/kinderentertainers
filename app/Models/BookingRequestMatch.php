<?php

namespace App\Models;

use App\Enums\BookingRequestMatchStatus;
use App\Support\Models\HasPublicIdentifier;
use Database\Factories\BookingRequestMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'booking_request_id',
    'entertainer_id',
    'match_score',
    'distance_km',
    'travel_minutes',
    'score_breakdown',
    'price_indication_cents',
    'response_message',
    'matched_at',
    'responded_at',
    'selected_at',
])]
class BookingRequestMatch extends Model
{
    /** @use HasFactory<BookingRequestMatchFactory> */
    use HasFactory, HasPublicIdentifier, SoftDeletes;

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
            'match_score' => 'integer',
            'distance_km' => 'decimal:1',
            'travel_minutes' => 'integer',
            'score_breakdown' => 'array',
            'price_indication_cents' => 'integer',
            'matched_at' => 'datetime',
            'responded_at' => 'datetime',
            'selected_at' => 'datetime',
        ];
    }
}
