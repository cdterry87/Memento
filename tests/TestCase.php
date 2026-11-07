<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Feature tests don't need compiled frontend assets.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }
}
