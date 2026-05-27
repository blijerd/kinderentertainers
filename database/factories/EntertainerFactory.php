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
            'gallery_photo_paths' => [],
            'short_introduction' => fake()->sentence(12),
            'bio' => fake()->paragraphs(3, true),
            'profile_highlights' => fake()->randomElements([
                'Interactief programma met veel persoonlijke aandacht',
                'Ervaring met kinderfeestjes, scholen en bedrijfsevents',
                'Materiaal en basisgeluid worden zelf meegenomen',
                'Flexibel programma voor kleine en grote groepen',
            ], 3),
            'audience_age_range' => fake()->randomElement(['3 t/m 8 jaar', '4 t/m 10 jaar', '6 t/m 12 jaar']),
            'event_types' => fake()->randomElements(['Kinderfeestje', 'School', 'Bedrijfsevent', 'Festival'], fake()->numberBetween(1, 3)),
            'languages' => fake()->randomElements(['Nederlands', 'Engels', 'Duits'], fake()->numberBetween(1, 2)),
            'rating' => fake()->randomElement([4.2, 4.5, 4.8, 5.0]),
            'reviews_count' => fake()->numberBetween(3, 80),
            'performance_duration_minutes' => fake()->randomElement([45, 60, 90, 120]),
            'setup_time_minutes' => fake()->randomElement([15, 30, 45]),
            'show_reel_url' => null,
            'practical_requirements' => fake()->sentence(14),
            'packages' => [
                ['name' => 'Basisfeest', 'price_cents' => 17500, 'description' => 'Kort programma voor thuis of kleine groep.'],
            ],
            'extras' => [
                ['name' => 'Glittertattoos', 'price_cents' => 3500, 'description' => 'Extra activiteit na het optreden.'],
            ],
            'cancellation_policy' => 'Kosteloos annuleren tot 14 dagen voor de boeking.',
            'deposit_percentage' => 25,
            'city' => fake()->city(),
            'region' => fake()->randomElement(['Noord-Holland', 'Zuid-Holland', 'Utrecht', 'Noord-Brabant', 'Gelderland']),
            'working_radius_km' => fake()->randomElement([25, 40, 60, 80]),
            'active' => true,
            'featured' => fake()->boolean(30),
            'profile_complete' => true,
            'profile_quality_score' => 75,
            'average_response_minutes' => fake()->randomElement([45, 120, 360, null]),
        ];
    }
}
