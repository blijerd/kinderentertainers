<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\CustomerType;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingRequest>
 */
class BookingRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'entertainer_id' => Entertainer::factory(),
            'skill_id' => Skill::factory(),
            'customer_type' => fake()->randomElement(CustomerType::cases()),
            'name' => fake()->name(),
            'company_name' => fake()->optional()->company(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'event_date' => fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'address' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'children_count' => fake()->numberBetween(8, 35),
            'children_ages' => fake()->randomElement(['4-6 jaar', '6-8 jaar', '8-10 jaar']),
            'desired_skills' => ['Schminker'],
            'message' => fake()->paragraph(),
            'status' => fake()->randomElement(BookingStatus::cases()),
            'internal_note' => fake()->optional()->sentence(),
        ];
    }
}
