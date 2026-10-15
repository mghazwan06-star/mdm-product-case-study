<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\ExplodingProvider;
use Tests\TestCase;

/**
 * Findings F2 and F12: technical details (database address, SQL, traces) never
 * reach the website, even with debug mode on. They stay in the server log.
 */
class PlainErrorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_f12_database_unreachable_during_sync_gives_a_plain_message(): void
    {
        $response = $this->whileDatabaseUnreachable(fn () => $this->postJson('/api/sync'));

        $response->assertStatus(503)->assertExactJson([
            'status' => 'failed',
            'message' => "WorkWize's database isn't reachable right now. Nothing was changed. Try again in a minute.",
        ]);
        $this->assertNoTechnicalDetails($response);
    }

    public function test_f12_database_unreachable_on_a_page_gives_a_plain_message(): void
    {
        foreach (['/api/assets', '/api/employees'] as $url) {
            $response = $this->whileDatabaseUnreachable(fn () => $this->getJson($url));

            $response->assertStatus(503)->assertExactJson([
                'message' => "WorkWize's database isn't reachable right now. Try again in a minute.",
            ]);
            $this->assertNoTechnicalDetails($response);
        }
    }

    public function test_f2_an_unexpected_error_during_sync_gives_a_plain_message(): void
    {
        config(['mdm.providers.exploding' => ExplodingProvider::class, 'mdm.default_provider' => 'exploding']);

        $response = $this->postJson('/api/sync');

        $response->assertStatus(500)->assertExactJson([
            'status' => 'failed',
            'message' => 'Something went wrong during the sync. Nothing was changed. Try again in a minute.',
        ]);
        $this->assertNoTechnicalDetails($response);
        $this->assertStringNotContainsString(ExplodingProvider::SECRET, $response->getContent());
    }

    private function whileDatabaseUnreachable(callable $request): TestResponse
    {
        // Nothing listens on port 1, so every query fails with "Connection refused", like a stopped database.
        config(['database.connections.unreachable' => array_merge(config('database.connections.mysql'), ['host' => '127.0.0.1', 'port' => 1])]);
        config(['database.default' => 'unreachable']);

        try {
            return $request();
        } finally {
            config(['database.default' => 'mysql']); // so the test database can be tidied up afterwards
        }
    }

    private function assertNoTechnicalDetails(TestResponse $response): void
    {
        foreach (['SQLSTATE', 'trace', 'exception', '3306', 'mysql', 'Connection refused', '/var/www'] as $detail) {
            $this->assertStringNotContainsStringIgnoringCase($detail, $response->getContent());
        }
    }
}
