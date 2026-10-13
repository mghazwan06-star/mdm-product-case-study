<?php

namespace Tests\Feature\Mdm;

use App\Enums\SyncRunStatus;
use App\Mdm\Jamf\JamfProvider;
use App\Mdm\MdmSyncService;
use App\Models\Asset;
use App\Models\SyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Only one sync at a time (docs/DECISIONS.md row 11).
 */
class SyncLockTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'A sync is already running. Please wait for it to finish.';

    public function test_t25_a_second_sync_while_one_is_running_is_refused_and_recorded(): void
    {
        $running = Cache::lock(MdmSyncService::LOCK_NAME, 60);
        $this->assertTrue($running->get());

        $result = app(MdmSyncService::class)->sync(new JamfProvider);

        $this->assertSame('refused', $result->status);
        $this->assertSame(self::MESSAGE, $result->message);
        $this->assertSame(0, Asset::count());
        $run = SyncRun::sole();
        $this->assertSame(SyncRunStatus::Refused, $run->status);
        $this->assertSame(self::MESSAGE, $run->message);

        $running->release();
    }

    public function test_t25_the_sync_button_gets_a_409_with_the_plain_message(): void
    {
        $running = Cache::lock(MdmSyncService::LOCK_NAME, 60);
        $running->get();

        $this->postJson('/api/sync')
            ->assertStatus(409)
            ->assertJsonPath('status', 'refused')
            ->assertJsonPath('message', self::MESSAGE);

        $running->release();
    }

    public function test_the_lock_is_released_after_a_sync_so_the_next_one_runs(): void
    {
        app(MdmSyncService::class)->sync(new JamfProvider);
        $second = app(MdmSyncService::class)->sync(new JamfProvider);

        $this->assertTrue($second->succeeded());
        $this->assertTrue(Cache::lock(MdmSyncService::LOCK_NAME, 1)->get());
    }

    public function test_the_lock_is_released_after_a_failed_sync_too(): void
    {
        $failed = app(MdmSyncService::class)->sync(new JamfProvider('/nowhere.json'));
        $next = app(MdmSyncService::class)->sync(new JamfProvider);

        $this->assertSame('failed', $failed->status);
        $this->assertTrue($next->succeeded());
    }

    public function test_the_sync_command_is_refused_too(): void
    {
        $running = Cache::lock(MdmSyncService::LOCK_NAME, 60);
        $running->get();

        $this->assertSame(1, Artisan::call('mdm:sync'));
        $this->assertStringContainsString(self::MESSAGE, Artisan::output());

        $running->release();
    }
}
