<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the privacy policy to visitors', function () {
    $this->get(route('privacy'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Privacy'));
});

it('shows the privacy policy to signed-in users too', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('privacy'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Privacy'));
});
