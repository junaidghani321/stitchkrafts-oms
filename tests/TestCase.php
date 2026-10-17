<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * A guard, not a formality: the suite runs RefreshDatabase, so pointing it
     * at the live schema would wipe real orders. phpunit.xml pins the
     * connection to oms_stitchkraft_test; this fails the run if anything
     * manages to change that.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $database = config('database.connections.'.config('database.default').'.database');

        if (! str_ends_with((string) $database, '_test')) {
            throw new RuntimeException("Refusing to run tests against '{$database}' — the test database name must end in _test.");
        }
    }
}
