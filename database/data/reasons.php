<?php

/**
 * The reasons offered in the check-in, in picker order.
 *
 * To add a reason, add a row and run `php artisan db:seed --class=LookupSeeder`.
 * To retire one, set 'is_active' => false. Never remove a row or change a
 * slug, because past entries refer to it.
 *
 * @return list<array{slug: string, label: string, emoji: string, is_active?: bool}>
 */
return [
    ['slug' => 'work', 'label' => 'Work', 'emoji' => '💼'],
    ['slug' => 'family', 'label' => 'Family', 'emoji' => '🏠'],
    ['slug' => 'relationship', 'label' => 'Relationship', 'emoji' => '💕'],
    ['slug' => 'friends', 'label' => 'Friends', 'emoji' => '🤝'],
    ['slug' => 'school', 'label' => 'School', 'emoji' => '🎓'],
    ['slug' => 'travel', 'label' => 'Travel', 'emoji' => '🌍'],
    ['slug' => 'entertainment', 'label' => 'Entertainment', 'emoji' => '🎬'],
    ['slug' => 'personal-goals', 'label' => 'Personal goals', 'emoji' => '🎯'],
    ['slug' => 'finances', 'label' => 'Finances', 'emoji' => '💰'],
    ['slug' => 'health', 'label' => 'Health', 'emoji' => '💪'],
    ['slug' => 'hobbies', 'label' => 'Hobbies', 'emoji' => '🎨'],
    ['slug' => 'other', 'label' => 'Other', 'emoji' => '✨'],
];
