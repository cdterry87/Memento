<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('reasons')->insert([
            ['reason' => 'Work', 'icon' => 'mdi-email'],
            ['reason' => 'Family', 'icon' => 'mdi-home'],
            ['reason' => 'Relationship', 'icon' => 'mdi-heart-multiple'],
            ['reason' => 'Friends', 'icon' => 'mdi-account-group'],
            ['reason' => 'School', 'icon' => 'mdi-school'],
            ['reason' => 'Travel', 'icon' => 'mdi-earth'],
            ['reason' => 'Other', 'icon' => 'mdi-plus']
        ]);
    }
}
