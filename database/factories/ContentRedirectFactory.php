<?php

namespace Database\Factories;

use App\Models\ContentRedirect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentRedirect>
 */
class ContentRedirectFactory extends Factory
{
    public function definition(): array
    {
        $from = '/oud-'.fake()->unique()->slug(2);

        return [
            'from_path' => $from,
            'to_url' => '/kinderentertainers',
            'status_code' => 301,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
