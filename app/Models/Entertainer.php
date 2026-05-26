<?php

namespace App\Models;

use Database\Factories\EntertainerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'profile_photo_path',
    'short_introduction',
    'bio',
    'city',
    'region',
    'working_radius_km',
    'active',
    'featured',
    'profile_complete',
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

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'featured' => 'boolean',
            'profile_complete' => 'boolean',
            'working_radius_km' => 'integer',
        ];
    }
}
