<?php

namespace Database\Factories;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'intro' => fake()->sentence(),
            'body' => fake()->paragraphs(2, true),
            'is_published' => true,
            'published_at' => now()->subMinute(),
            'noindex' => false,
        ];
    }

    public function draft(): static
    {
        return $this->unpublished();
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'is_published' => true,
            'published_at' => now()->addDay(),
        ]);
    }

    public function noindex(): static
    {
        return $this->state(fn (): array => [
            'noindex' => true,
        ]);
    }
}
