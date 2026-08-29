<?php

namespace Database\Factories;

use App\Enums\BookingRequestMatchStatus;
use App\Models\BookingRequest;
use App\Models\BookingRequestMatch;
use App\Models\Entertainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingRequestMatch>
 */
class BookingRequestMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_request_id' => BookingRequest::factory(),
            'entertainer_id' => Entertainer::factory(),
            'status' => BookingRequestMatchStatus::Available,
            'matched_at' => now(),
        ];
    }
}
