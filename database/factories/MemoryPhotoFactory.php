<?php

namespace Database\Factories;

use App\Models\Memory;
use App\Models\MemoryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MemoryPhoto>
 */
class MemoryPhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::random(40);

        return [
            'memory_id' => Memory::factory(),
            'path' => "photos/{$name}.jpg",
            'thumbnail_path' => "photos/{$name}_thumb.jpg",
            'width' => 2048,
            'height' => 1536,
            'size_bytes' => fake()->numberBetween(100_000, 900_000),
            'position' => 1,
        ];
    }
}
