<?php

namespace Database\Factories;

use App\Models\Mood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mood>
 */
class MoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->word();

        return [
            'slug' => str($label)->slug()->toString(),
            'label' => ucfirst($label),
            'emoji' => fake()->randomElement(['😀', '🥳', '😴', '🤔', '😌']),
            'score' => fake()->numberBetween(1, 5),
            'position' => fake()->numberBetween(100, 200),
            'is_active' => true,
        ];
    }

    /**
     * A mood that has been retired from the picker.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
