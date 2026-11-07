<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * The home page renders the Welcome Inertia page.
     */
    public function test_the_home_page_renders_the_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('appName', config('app.name'))
        );
    }
}
