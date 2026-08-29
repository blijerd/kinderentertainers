<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use App\Support\Models\HasPublicIdentifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

#[Fillable(['entertainer_id', 'name', 'rule_type', 'weekdays', 'starts_on', 'ends_on', 'start_time', 'end_time', 'status', 'internal_note'])]
class AvailabilityRule extends Model
{
    use HasPublicIdentifier, SoftDeletes;

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class);
    }

    public function appliesTo(string $date): bool
    {
        $date = Carbon::parse($date);

        if ($this->starts_on && $date->lt($this->starts_on)) {
            return false;
        }

        if ($this->ends_on && $date->gt($this->ends_on)) {
            return false;
        }

        return match ($this->rule_type) {
            'weekly' => in_array($date->dayOfWeek, $this->weekdays ?? [], true),
            'date_range' => true,
            default => false,
        };
    }

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'status' => AvailabilityStatus::class,
        ];
    }
}
