<?php

/**
 * The moods offered in the check-in, in picker order.
 *
 * To add a mood, add a row and run `php artisan db:seed --class=LookupSeeder`.
 * To retire one, set 'is_active' => false. Never remove a row or change a
 * slug, because past entries refer to it.
 *
 * Score runs from 1 (worst) to 5 (best) and drives analytics and chart
 * colours, so a new mood needs no other changes.
 *
 * @return list<array{slug: string, label: string, emoji: string, score: int<1, 5>, is_active?: bool}>
 */
return [
    ['slug' => 'awful', 'label' => 'Awful', 'emoji' => '😭', 'score' => 1],
    ['slug' => 'bad', 'label' => 'Bad', 'emoji' => '😞', 'score' => 2],
    ['slug' => 'exhausting', 'label' => 'Exhausting', 'emoji' => '😵', 'score' => 2],
    ['slug' => 'frustrating', 'label' => 'Frustrating', 'emoji' => '😠', 'score' => 2],
    ['slug' => 'okay', 'label' => 'Okay', 'emoji' => '😐', 'score' => 3],
    ['slug' => 'good', 'label' => 'Good', 'emoji' => '🙂', 'score' => 4],
    ['slug' => 'great', 'label' => 'Great', 'emoji' => '😄', 'score' => 5],
    ['slug' => 'fun', 'label' => 'Fun', 'emoji' => '😜', 'score' => 5],
    ['slug' => 'awesome', 'label' => 'Awesome', 'emoji' => '😎', 'score' => 5],
    ['slug' => 'romantic', 'label' => 'Romantic', 'emoji' => '😍', 'score' => 5],
    ['slug' => 'exciting', 'label' => 'Exciting', 'emoji' => '🤩', 'score' => 5],
    ['slug' => 'energetic', 'label' => 'Energetic', 'emoji' => '⚡', 'score' => 5],
];
