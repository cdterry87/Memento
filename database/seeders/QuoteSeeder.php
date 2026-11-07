<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the quotes with those in database/data/quotes.php, in file order,
 * so the daily rotation follows the file. Nothing refers to a quote, so it
 * is safe to run again at any time, including in production.
 */
class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var list<array{text: string, author: string, source: string}> $quotes */
        $quotes = require database_path('data/quotes.php');

        DB::transaction(function () use ($quotes) {
            DB::table('quotes')->delete();
            DB::table('quotes')->insert(
                array_map(fn (array $quote) => ['text' => $quote['text'], 'author' => $quote['author']], $quotes),
            );
        });
    }
}
