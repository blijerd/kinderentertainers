<?php

namespace App\Actions;

use App\Enums\BookingStatus;
use App\Models\BookingRequest;

class CreateBookingRequest
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): BookingRequest
    {
        $data['status'] ??= BookingStatus::New;

        return BookingRequest::create($data);
    }
}
