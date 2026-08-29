<?php

namespace App\Filament\Resources\LandingPages\Pages;

use App\Actions\UpsertLandingPage;
use App\Filament\Resources\LandingPages\LandingPageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource = LandingPageResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(UpsertLandingPage::class)->handle(null, $data);
    }
}
