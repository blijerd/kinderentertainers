<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Support\Models\HasPublicIdentifier;
use Database\Factories\RateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'entertainer_id',
    'customer_type',
    'starting_rate_cents',
    'hourly_rate_cents',
    'minimum_hours',
    'travel_cost_cents_per_km',
    'vat_included',
    'remarks',
])]
class Rate extends Model
{
    /** @use HasFactory<RateFactory> */
    use HasFactory, HasPublicIdentifier, SoftDeletes;

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class);
    }

    protected function casts(): array
    {
        return [
            'customer_type' => CustomerType::class,
            'starting_rate_cents' => 'integer',
            'hourly_rate_cents' => 'integer',
            'minimum_hours' => 'decimal:1',
            'travel_cost_cents_per_km' => 'integer',
            'vat_included' => 'boolean',
        ];
    }
}
