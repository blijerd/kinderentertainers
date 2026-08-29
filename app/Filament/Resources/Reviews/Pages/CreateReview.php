<?php

namespace App\Filament\Resources\Reviews\Pages;

use App\Actions\ModerateReview;
use App\Enums\ReviewStatus;
use App\Filament\Resources\Reviews\ReviewResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $review = static::getModel()::query()->make();

        return app(ModerateReview::class)->handle($review, [
            ...$data,
            'status' => $data['status'] ?? ReviewStatus::Pending,
        ]);
    }
}
