<?php

namespace App\Services;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use App\Models\LegalDocumentVersion;

class LegalDocumentRepository
{
    public function currentVersion(LegalDocumentType $type): ?LegalDocumentVersion
    {
        return LegalDocumentVersion::query()
            ->with('legalDocument')
            ->whereHas('legalDocument', fn ($query) => $query
                ->where('type', $type->value)
                ->where('is_active', true))
            ->published()
            ->where('published_at', '<=', now())
            ->where(fn ($query) => $query
                ->whereNull('replaced_at')
                ->orWhere('replaced_at', '>', now()))
            ->latest('published_at')
            ->latest('id')
            ->first();
    }

    public function ensureDocument(LegalDocumentType $type): LegalDocument
    {
        return LegalDocument::query()->firstOrCreate(
            ['type' => $type],
            ['title' => $type->label(), 'is_active' => true],
        );
    }
}
