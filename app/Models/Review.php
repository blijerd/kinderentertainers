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
    'token_expires_at',
    'link_sent_at',
    'submitted_at',
    'published_at',
    'submission_ip',
    'submission_user_agent',
    'moderation_note',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Review $review): void {
            $review->token ??= Str::random(40);
            $review->token_expires_at ??= now()->addDays(45);
            $review->status ??= ReviewStatus::Pending;
        });

        static::saved(function (Review $review): void {
            if ($review->wasRecentlyCreated || $review->wasChanged(['status', 'published_at', 'rating'])) {
                $approvedReviews = $review->entertainer?->approvedReviews();
                $reviewsCount = $approvedReviews?->count() ?? 0;
                $rating = $reviewsCount > 0 ? round((float) $approvedReviews->avg('rating'), 1) : null;

                $review->entertainer?->update([
                    'rating' => $rating,
                    'reviews_count' => $reviewsCount,
                ]);
            }
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
            'token_expires_at' => 'datetime',
            'link_sent_at' => 'datetime',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }
}
