<?php

namespace Tests;

use App\Models\Role;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The roles cache is process-wide; database refreshes between tests
        // must not leave rows from a previous test behind.
        Role::flushCache();
    }
}
