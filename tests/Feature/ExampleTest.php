<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the welcome page at the home route', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('appName', config('app.name'))
        );
});
