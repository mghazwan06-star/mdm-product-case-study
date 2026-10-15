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

    public function test_f10_after_a_crash_the_refusal_says_the_last_sync_was_interrupted_and_when_to_retry(): void
    {
        $crashed = $this->crashedSync();
        $this->travel(2)->minutes();

        $response = $this->postJson('/api/sync');

        $response->assertStatus(409)->assertJsonPath(
            'message',
            'The last sync was interrupted. Nothing was changed. You can sync again in about 3 minutes.',
        );
        $this->assertSame(SyncRunStatus::Interrupted, $crashed->fresh()->status);
        $this->assertSame(
            'Interrupted: the sync stopped before it finished (for example, WorkWize or its database restarted). Nothing was changed.',
            $crashed->fresh()->message,
        );

        $this->travel(150)->seconds();
        $this->postJson('/api/sync')->assertJsonPath(
            'message',
            'The last sync was interrupted. Nothing was changed. You can sync again in about 1 minute.',
        );
    }

    public function test_f10_once_the_lock_runs_out_the_next_sync_works_and_no_row_stays_running(): void
    {
        $crashed = $this->crashedSync();
        $this->travel(5)->minutes();
        $this->travel(1)->seconds();

        $this->postJson('/api/sync')->assertOk()->assertJsonPath('status', 'success');

        $this->assertSame(SyncRunStatus::Interrupted, $crashed->fresh()->status);
        $this->assertSame(0, SyncRun::where('status', SyncRunStatus::Running)->count());
    }

    public function test_f10_a_sync_that_really_is_running_still_says_so(): void
    {
        $running = $this->crashedSync();
        $this->travel(10)->seconds();

        $this->postJson('/api/sync')->assertStatus(409)->assertJsonPath('message', self::MESSAGE);
        $this->assertSame(SyncRunStatus::Running, $running->fresh()->status);
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

    /**
     * What a crash leaves behind: a history row stuck on "running" and a lock that
     * nobody will release (it expires after 5 minutes).
     */
    private function crashedSync(): SyncRun
    {
        $this->assertTrue(Cache::lock(MdmSyncService::LOCK_NAME, 300)->get());

        return SyncRun::create(['provider' => 'jamf', 'status' => SyncRunStatus::Running, 'started_at' => now()]);
    }
}
