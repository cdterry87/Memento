<?php

namespace Database\Factories;

use App\Models\Reason;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reason>
 */
class ReasonFactory extends Factory
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
            'position' => fake()->numberBetween(100, 200),
            'is_active' => true,
        ];
    }

    /**
     * A reason that has been retired from the picker.
     */
    public function retired(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
