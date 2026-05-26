<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use Database\Factories\BookingRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'entertainer_id',
    'skill_id',
    'customer_type',
    'name',
    'company_name',
    'email',
    'phone',
    'event_date',
    'start_time',
    'end_time',
    'address',
    'postal_code',
    'city',
    'children_count',
    'children_ages',
    'desired_skills',
    'message',
    'status',
    'internal_note',
])]
class BookingRequest extends Model
{
    /** @use HasFactory<BookingRequestFactory> */
    use HasFactory;

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BookingRequestMatch::class);
    }

    public function isGeneral(): bool
    {
        return $this->entertainer_id === null && $this->skill_id !== null;
    }

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'skill_id' => 'integer',
            'event_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'children_count' => 'integer',
            'desired_skills' => 'array',
            'status' => BookingStatus::class,
        ];
    }
}
