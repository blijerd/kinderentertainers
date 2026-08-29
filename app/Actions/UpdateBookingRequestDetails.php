<?php

namespace App\Actions;

use App\Models\BookingRequest;
use Illuminate\Validation\ValidationException;

class UpdateBookingRequestDetails
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(BookingRequest $bookingRequest, array $data): BookingRequest
    {
        if (array_key_exists('status', $data)) {
            throw ValidationException::withMessages([
                'status' => 'Statuswijzigingen gaan via een dedicated Action.',
            ]);
        }

        $bookingRequest->update($data);

        return $bookingRequest->refresh();
    }
}
