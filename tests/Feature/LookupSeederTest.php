<?php

use App\Models\Mood;
use App\Models\Reason;
use Database\Seeders\LookupSeeder;

it('loads every mood and reason from the data files', function () {
    $this->seed(LookupSeeder::class);

    $moods = require database_path('data/moods.php');
    $reasons = require database_path('data/reasons.php');

    expect(Mood::pickable()->pluck('slug')->all())->toBe(array_column($moods, 'slug'))
        ->and(Reason::pickable()->pluck('slug')->all())->toBe(array_column($reasons, 'slug'));
});

it('can run again without creating duplicates', function () {
    $this->seed(LookupSeeder::class);
    $this->seed(LookupSeeder::class);

    expect(Mood::count())->toBe(12)
        ->and(Reason::count())->toBe(11);
});

it('restores edited rows from the data files by slug', function () {
    $this->seed(LookupSeeder::class);
    Mood::where('slug', 'great')->update(['label' => 'Changed', 'is_active' => false]);

    $this->seed(LookupSeeder::class);

    expect(Mood::where('slug', 'great')->sole())
        ->label->toBe('Great')
        ->is_active->toBeTrue();
});

it('includes Energetic and Health', function () {
    $this->seed(LookupSeeder::class);

    expect(Mood::where('slug', 'energetic')->sole())
        ->emoji->toBe('⚡')
        ->score->toBe(5)
        ->and(Reason::where('slug', 'health')->sole()->emoji)->toBe('💪');
});
