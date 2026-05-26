<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Entertainer;
use App\Models\Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rate>
 */
class RateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'entertainer_id' => Entertainer::factory(),
            'customer_type' => fake()->randomElement(CustomerType::cases()),
            'starting_rate_cents' => fake()->randomElement([12500, 15000, 17500, 22500]),
            'hourly_rate_cents' => fake()->randomElement([7500, 9500, 12500]),
            'minimum_hours' => fake()->randomElement([1, 1.5, 2]),
            'travel_cost_cents_per_km' => fake()->randomElement([35, 45, 55]),
            'vat_included' => true,
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
