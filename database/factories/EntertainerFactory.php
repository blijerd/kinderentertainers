<?php

namespace Database\Factories;

use App\Models\Entertainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Entertainer>
 */
class EntertainerFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'profile_photo_path' => null,
            'short_introduction' => fake()->sentence(12),
            'bio' => fake()->paragraphs(3, true),
            'city' => fake()->city(),
            'region' => fake()->randomElement(['Noord-Holland', 'Zuid-Holland', 'Utrecht', 'Noord-Brabant', 'Gelderland']),
            'working_radius_km' => fake()->randomElement([25, 40, 60, 80]),
            'active' => true,
            'featured' => fake()->boolean(30),
            'profile_complete' => true,
        ];
    }
}
