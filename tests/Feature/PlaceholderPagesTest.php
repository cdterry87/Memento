<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders each app screen with its page component', function (string $routeName, string $component) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'today' => ['today', 'Today'],
    'timeline' => ['timeline', 'Timeline'],
    'recap' => ['recap', 'Recap'],
    'account' => ['account', 'Account'],
]);

it('applies a saved theme before the first paint', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee("localStorage.getItem('memento.theme')", escape: false);
});
