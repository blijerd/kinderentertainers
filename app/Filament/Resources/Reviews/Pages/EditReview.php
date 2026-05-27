<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReview extends EditRecord
{
    protected static string $resource = ReviewResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === ReviewStatus::Approved->value && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        if (($data['status'] ?? null) !== ReviewStatus::Approved->value) {
            $data['published_at'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
