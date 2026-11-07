<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

dataset('app screens', [
    'today' => ['today', 'Today'],
    'timeline' => ['timeline', 'Timeline'],
    'recap' => ['recap', 'Recap'],
    'account' => ['account', 'Account'],
]);

it('renders each app screen for a signed-in user', function (string $routeName, string $component) {
    $this->actingAs(User::factory()->create())
        ->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with('app screens');

it('sends guests to sign in', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with('app screens');

it("applies a visitor's saved theme before the first paint", function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee("localStorage.getItem('memento.theme')", escape: false);
});
