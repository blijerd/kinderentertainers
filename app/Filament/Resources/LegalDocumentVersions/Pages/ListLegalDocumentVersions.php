<?php

namespace App\Filament\Resources\LegalDocumentVersions\Pages;

use App\Filament\Resources\LegalDocumentVersions\LegalDocumentVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLegalDocumentVersions extends ListRecords
{
    protected static string $resource = LegalDocumentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
