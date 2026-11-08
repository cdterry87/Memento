<?php

namespace Database\Factories;

use App\Models\DataExport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<DataExport>
 */
class DataExportFactory extends Factory
{
    /**
     * A requested export whose job hasn't run yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }

    /**
     * A built export whose link still works.
     */
    public function ready(): static
    {
        return $this->state(fn () => [
            'path' => 'exports/'.Str::random(40).'.zip',
            'size_bytes' => fake()->numberBetween(1_000, 900_000),
            'completed_at' => now(),
            'expires_at' => now()->addHours(DataExport::EXPIRES_AFTER_HOURS),
        ]);
    }

    /**
     * A built export whose link has run out.
     */
    public function expired(): static
    {
        return $this->ready()->state(fn () => [
            'completed_at' => now()->subHours(DataExport::EXPIRES_AFTER_HOURS + 1),
            'expires_at' => now()->subHour(),
        ]);
    }
}
