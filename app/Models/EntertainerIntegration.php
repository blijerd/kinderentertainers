<?php

namespace App\Models;

use App\Enums\IntegrationProvider;
use App\Support\Models\HasPublicIdentifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'entertainer_id',
    'provider',
    'enabled',
    'credentials',
    'settings',
    'last_checked_at',
    'last_check_status',
    'last_check_message',
])]
class EntertainerIntegration extends Model
{
    use HasPublicIdentifier, SoftDeletes;

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class);
    }

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'enabled' => 'boolean',
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'last_checked_at' => 'datetime',
        ];
    }
}
