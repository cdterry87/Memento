<?php

namespace Database\Factories;

use App\Models\Memory;
use App\Models\Mood;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Memory>
 */
class MemoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'mood_id' => Mood::factory(),
            'date' => fake()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'body' => fake()->optional()->sentence(),
        ];
    }
}
