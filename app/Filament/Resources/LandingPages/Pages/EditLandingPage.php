<?php

namespace App\Filament\Resources\LandingPages\Pages;

use App\Actions\UpsertLandingPage;
use App\Filament\Resources\LandingPages\LandingPageResource;
use App\Models\LandingPage;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof LandingPage);

        return app(UpsertLandingPage::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
