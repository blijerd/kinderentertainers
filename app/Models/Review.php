<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'booking_request_id',
    'entertainer_id',
    'customer_name',
    'customer_email',
    'rating',
    'title',
    'body',
    'status',
    'token',
    'link_sent_at',
    'submitted_at',
    'published_at',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Review $review): void {
            $review->token ??= Str::random(40);
            $review->status ??= ReviewStatus::Pending;
        });
    }

    public function bookingRequest(): BelongsTo
    {
        return $this->belongsTo(BookingRequest::class);
    }

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class);
    }

    public function isSubmitted(): bool
    {
        return $this->submitted_at !== null;
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'link_sent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
