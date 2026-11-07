<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the sign-in screen', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/Login'));
});

it('signs a user in and takes them to today', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('today'));

    $this->assertAuthenticatedAs($user);
});

it('refuses a wrong password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'not-the-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('throttles repeated sign-in attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong'])
        ->assertTooManyRequests();
});

it('signs a user out with a POST request only', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/logout')->assertMethodNotAllowed();

    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
});

it('sends signed-in users from the welcome page to today', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertRedirect(route('today'));
});

it('shares only the fields the interface needs about the signed-in user', function () {
    $user = User::factory()->create(['name' => 'Ada', 'timezone' => 'Europe/London']);

    $this->actingAs($user)
        ->get(route('today'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user', [
                'name' => 'Ada',
                'email' => $user->email,
                'emailVerified' => true,
                'timezone' => 'Europe/London',
                'theme' => 'system',
            ])
        );
});
