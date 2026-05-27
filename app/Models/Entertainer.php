<?php

namespace App\Models;

use App\Enums\AccountingProvider;
use App\Enums\ReviewStatus;
use Database\Factories\EntertainerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'profile_photo_path',
    'gallery_photo_paths',
    'short_introduction',
    'bio',
    'profile_highlights',
    'audience_age_range',
    'event_types',
    'languages',
    'rating',
    'reviews_count',
    'performance_duration_minutes',
    'setup_time_minutes',
    'show_reel_url',
    'practical_requirements',
    'packages',
    'extras',
    'cancellation_policy',
    'deposit_percentage',
    'city',
    'region',
    'working_radius_km',
    'accounting_provider',
    'accounting_notes',
    'active',
    'featured',
    'profile_complete',
    'profile_quality_score',
    'average_response_minutes',
])]
class Entertainer extends Model
{
    /** @use HasFactory<EntertainerFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class)->withTimestamps();
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function availabilityRules(): HasMany
    {
        return $this->hasMany(AvailabilityRule::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }

    public function consumerRate(): HasOne
    {
        return $this->hasOne(Rate::class)->where('customer_type', 'consument');
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    public function integrations(): HasMany
    {
        return $this->hasMany(EntertainerIntegration::class);
    }

    public function bookingRequestMatches(): HasMany
    {
        return $this->hasMany(BookingRequestMatch::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'customer_favorites')->withTimestamps();
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()
            ->where('status', ReviewStatus::Approved->value)
            ->whereNotNull('published_at');
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_path ? Storage::disk('public')->url($this->profile_photo_path) : null;
    }

    public function galleryPhotoUrls(): array
    {
        return collect($this->gallery_photo_paths ?? [])
            ->filter()
            ->map(fn (string $path): string => Storage::disk('public')->url($path))
            ->values()
            ->all();
    }

    public function profileHighlightsList(): array
    {
        return collect($this->profile_highlights ?? [])
            ->filter()
            ->values()
            ->all();
    }

    public function eventTypesList(): array
    {
        return collect($this->event_types ?? [])
            ->filter()
            ->values()
            ->all();
    }

    public function languagesList(): array
    {
        return collect($this->languages ?? [])
            ->filter()
            ->values()
            ->all();
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'featured' => 'boolean',
            'profile_complete' => 'boolean',
            'gallery_photo_paths' => 'array',
            'profile_highlights' => 'array',
            'event_types' => 'array',
            'languages' => 'array',
            'packages' => 'array',
            'extras' => 'array',
            'rating' => 'decimal:1',
            'reviews_count' => 'integer',
            'working_radius_km' => 'integer',
            'performance_duration_minutes' => 'integer',
            'setup_time_minutes' => 'integer',
            'deposit_percentage' => 'integer',
            'profile_quality_score' => 'integer',
            'average_response_minutes' => 'integer',
            'accounting_provider' => AccountingProvider::class,
        ];
    }
}
