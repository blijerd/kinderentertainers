<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\BookingRequest;
use App\Models\Entertainer;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_request_id' => BookingRequest::factory(),
            'entertainer_id' => Entertainer::factory(),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => fake()->randomElement(ReviewStatus::cases()),
            'token' => Str::random(40),
            'link_sent_at' => fake()->optional()->dateTimeBetween('-1 month'),
            'submitted_at' => fake()->optional()->dateTimeBetween('-1 month'),
            'published_at' => null,
        ];
    }
}
