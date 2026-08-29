<?php

namespace App\Models;

use App\Support\Models\HasPublicIdentifier;
use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'description', 'icon', 'active'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory, HasPublicIdentifier, SoftDeletes;

    public function entertainers(): BelongsToMany
    {
        return $this->belongsToMany(Entertainer::class)->withTimestamps();
    }

    public function bookingRequests(): HasMany
    {
        return $this->hasMany(BookingRequest::class);
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
