<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TestDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_tests_use_the_separate_test_database(): void
    {
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('workwize_test', DB::connection()->getDatabaseName());
    }
}
