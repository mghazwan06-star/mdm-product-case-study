<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HealthAndRootTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_reports_backend_database_and_jamf_file(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('backend.ok', true)
            ->assertJsonPath('database.ok', true)
            ->assertJsonPath('jamf_file.ok', true)
            ->assertJsonPath('jamf_file.message', 'Found and readable');
    }

    public function test_health_check_reports_a_missing_jamf_file(): void
    {
        config(['mdm.jamf.mock_path' => '/nowhere/api-mock-response.json']);

        $this->getJson('/api/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('jamf_file.ok', false)
            ->assertJsonPath('jamf_file.message', 'Not found or not readable');
    }

    public function test_backend_address_points_to_the_website_and_health_check(): void
    {
        $this->getJson('/')
            ->assertOk()
            ->assertJsonPath('website', 'http://localhost:5173')
            ->assertJsonPath('health', url('/api/health'));
    }

    public function test_opening_the_sync_address_in_a_browser_explains_how_to_sync(): void
    {
        $this->getJson('/api/sync')
            ->assertStatus(405)
            ->assertExactJson(['message' => 'To run a sync, press Sync Now on the website (or send a POST request to /api/sync).']);
    }

    public function test_file_serving_and_upload_routes_are_switched_off(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }
}
