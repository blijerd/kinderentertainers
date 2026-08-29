<?php

namespace App\Models;

use App\Support\Models\HasPublicIdentifier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'legal_document_id',
    'version_label',
    'body',
    'published_at',
    'replaced_at',
])]
class LegalDocumentVersion extends Model
{
    use HasPublicIdentifier, SoftDeletes;

    public function legalDocument(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at');
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'replaced_at' => 'datetime',
        ];
    }
}
