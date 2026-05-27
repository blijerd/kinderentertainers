<?php

namespace App\Filament\Resources\LegalDocumentVersions\Pages;

use App\Filament\Resources\LegalDocumentVersions\LegalDocumentVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLegalDocumentVersion extends EditRecord
{
    protected static string $resource = LegalDocumentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
