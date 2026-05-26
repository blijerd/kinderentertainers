<?php

namespace Database\Factories;

use App\Enums\AvailabilityStatus;
use App\Models\Availability;
use App\Models\Entertainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Availability>
 */
class AvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'entertainer_id' => Entertainer::factory(),
            'date' => fake()->dateTimeBetween('tomorrow', '+6 weeks')->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '17:00',
            'status' => fake()->randomElement(AvailabilityStatus::cases()),
            'internal_note' => fake()->optional()->sentence(),
        ];
    }
}
