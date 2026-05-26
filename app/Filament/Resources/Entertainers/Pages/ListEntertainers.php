<?php

namespace App\Filament\Resources\Entertainers\Pages;

use App\Filament\Resources\Entertainers\EntertainerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntertainers extends ListRecords
{
    protected static string $resource = EntertainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
