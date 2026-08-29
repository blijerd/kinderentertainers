<?php

namespace App\Filament\Resources\BookingRequests\Pages;

use App\Actions\CreateBookingRequest as CreateBookingRequestAction;
use App\Enums\BookingStatus;
use App\Filament\Resources\BookingRequests\BookingRequestResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBookingRequest extends CreateRecord
{
    protected static string $resource = BookingRequestResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        unset($data['status']);
        $data['status'] = BookingStatus::New;

        return app(CreateBookingRequestAction::class)->handle($data);
    }
}
