<?php

namespace App\Models;

use App\Enums\LegalDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['type', 'title', 'is_active'])]
class LegalDocument extends Model
{
    public function versions(): HasMany
    {
        return $this->hasMany(LegalDocumentVersion::class);
    }

    public function currentVersion(): ?LegalDocumentVersion
    {
        return $this->versions()
            ->whereNotNull('published_at')
            ->whereNull('replaced_at')
            ->latest('published_at')
            ->latest('id')
            ->first();
    }

    protected function casts(): array
    {
        return [
            'type' => LegalDocumentType::class,
            'is_active' => 'boolean',
        ];
    }
}
