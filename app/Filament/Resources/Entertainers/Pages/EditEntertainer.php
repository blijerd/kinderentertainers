<?php

namespace App\Filament\Resources\Entertainers\Pages;

use App\Filament\Resources\Entertainers\EntertainerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntertainer extends EditRecord
{
    protected static string $resource = EntertainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
