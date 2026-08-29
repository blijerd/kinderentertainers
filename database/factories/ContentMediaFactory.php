<?php

namespace Database\Factories;

use App\Models\ContentMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentMedia>
 */
class ContentMediaFactory extends Factory
{
    public function definition(): array
    {
        $filename = Str::slug(fake()->unique()->words(2, true)).'.jpg';

        return [
            'original_filename' => $filename,
            'path' => 'content-media/'.$filename,
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'byte_size' => 1024,
            'checksum' => hash('sha256', fake()->unique()->uuid()),
            'alt_text' => fake()->sentence(3),
            'source_path' => 'media/'.$filename,
        ];
    }
}
