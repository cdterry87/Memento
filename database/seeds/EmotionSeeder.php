<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('emotions')->insert([
            ['emotion' => 'Exhausting', 'icon' => 'mdi-emoticon-dead-outline'],
            ['emotion' => 'Awful', 'icon' => 'mdi-emoticon-cry-outline'],
            ['emotion' => 'Bad', 'icon' => 'mdi-emoticon-sad-outline'],
            ['emotion' => 'Frustrating', 'icon' => 'mdi-emoticon-angry-outline'],
            ['emotion' => 'Okay', 'icon' => 'mdi-emoticon-neutral-outline'],
            ['emotion' => 'Good', 'icon' => 'mdi-emoticon-happy-outline'],
            ['emotion' => 'Great', 'icon' => 'mdi-emoticon-outline'],
            ['emotion' => 'Fun', 'icon' => 'mdi-emoticon-tongue-outline'],
            ['emotion' => 'Awesome', 'icon' => 'mdi-emoticon-cool-outline'],
            ['emotion' => 'Romantic', 'icon' => 'mdi-emoticon-kiss-outline'],
            ['emotion' => 'Exciting', 'icon' => 'mdi-emoticon-excited-outline']
        ]);
    }
}
