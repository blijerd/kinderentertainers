<?php

namespace App\Models;

use App\Support\Content\ContentRedirectPath;
use App\Support\Models\HasPublicIdentifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'from_path',
    'to_url',
    'status_code',
    'is_active',
    'source_path',
])]
class ContentRedirect extends Model
{
    use HasFactory, HasPublicIdentifier, SoftDeletes;

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function destinationUrl(?string $query = null): string
    {
        return ContentRedirectPath::withIncomingQuery($this->to_url, (string) $query);
    }

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
