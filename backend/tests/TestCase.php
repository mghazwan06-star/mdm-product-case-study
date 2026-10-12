<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safety net: refuse to run (and possibly wipe) anything but the test database.
     * Runs before Laravel's test helpers such as RefreshDatabase.
     */
    protected function setUpTraits()
    {
        $database = $this->app['db']->connection()->getDatabaseName();

        if ($database !== 'workwize_test') {
            throw new RuntimeException("Tests must run against workwize_test, not {$database}.");
        }

        return parent::setUpTraits();
    }
}
