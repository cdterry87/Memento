<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Loads moods and reasons from database/data. It upserts by slug, so it is
 * safe to run again at any time, including in production.
 */
class LookupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->upsertFrom('moods', ['label', 'emoji', 'score', 'position', 'is_active']);
        $this->upsertFrom('reasons', ['label', 'emoji', 'position', 'is_active']);
    }

    /**
     * @param  list<string>  $updatedColumns
     */
    private function upsertFrom(string $table, array $updatedColumns): void
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = require database_path("data/{$table}.php");

        $records = collect($rows)
            ->values()
            ->map(fn (array $row, int $index) => [
                ...$row,
                'position' => $index + 1,
                'is_active' => $row['is_active'] ?? true,
            ])
            ->all();

        DB::table($table)->upsert($records, ['slug'], $updatedColumns);
    }
}
