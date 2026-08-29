<?php

namespace Database\Factories;

use App\Models\LandingPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LandingPage>
 */
class LandingPageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'title' => rtrim($title, '.'),
            'slug' => Str::slug($title),
            'intro' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'cta_label' => 'Bekijk entertainers',
            'cta_url' => '/kinderentertainers',
            'is_published' => true,
            'published_at' => now()->subMinute(),
            'noindex' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
