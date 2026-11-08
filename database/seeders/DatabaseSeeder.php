<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DemoAccount;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([LookupSeeder::class, QuoteSeeder::class]);

        if (config('memento.demo_mode')) {
            $demo = app(DemoAccount::class)->reset();

            $this->command?->info("Demo account {$demo->email} created with {$demo->memories()->count()} entries.");
        }

        if (app()->isLocal()) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
