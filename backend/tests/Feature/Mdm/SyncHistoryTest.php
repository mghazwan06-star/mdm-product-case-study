<?php

namespace Tests\Feature\Mdm;

use App\Enums\SyncRunStatus;
use App\Mdm\Contracts\DeviceMapper;
use App\Mdm\Contracts\MdmProvider;
use App\Mdm\Data\DeviceData;
use App\Mdm\Data\OwnerData;
use App\Mdm\Data\SkippedDevice;
use App\Mdm\Jamf\JamfProvider;
use App\Mdm\MdmSyncService;
use App\Models\Asset;
use App\Models\SyncRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Every sync attempt is recorded in sync_runs (docs/DECISIONS.md rows 8 and 12).
 */
class SyncHistoryTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->tempFiles, is_file(...)));
        parent::tearDown();
    }

    public function test_t1_first_successful_sync_is_recorded_as_the_first_sync(): void
    {
        $result = $this->sync(new JamfProvider);

        $this->assertTrue($result->isFirstSync);
        $run = SyncRun::sole();
        $this->assertSame($result->syncRunId, $run->id);
        $this->assertSame(SyncRunStatus::Success, $run->status);
        $this->assertTrue($run->is_first_sync);
        $this->assertSame('jamf', $run->provider);
        $this->assertNotNull($run->finished_at);
        $this->assertSame(7, $run->counts['assets_created']);
        $this->assertSame(6, $run->counts['employees_created']);
        $this->assertSame([], $run->skipped);
    }

    public function test_later_syncs_are_not_the_first_sync(): void
    {
        $this->sync(new JamfProvider);
        $second = $this->sync(new JamfProvider);

        $this->assertFalse($second->isFirstSync);
        $this->assertSame(2, SyncRun::count());
        $this->assertFalse(SyncRun::find($second->syncRunId)->is_first_sync);
        $this->assertSame(7, SyncRun::find($second->syncRunId)->counts['assets_unchanged']);
    }

    public function test_t14_a_failed_attempt_is_recorded_with_the_plain_message_and_the_reason(): void
    {
        $result = $this->sync(new JamfProvider($this->tempFile('{"results": [')));

        $run = SyncRun::sole();
        $this->assertSame($result->syncRunId, $run->id);
        $this->assertSame(SyncRunStatus::Failed, $run->status);
        $this->assertSame("Couldn't read the Jamf list. Nothing was changed. Try again later.", $run->message);
        $this->assertSame('Jamf file is not valid JSON.', $run->error);
        $this->assertNotNull($run->finished_at);
        $this->assertFalse($run->is_first_sync);
    }

    public function test_after_only_failed_attempts_the_next_success_is_still_the_first_sync(): void
    {
        $this->sync(new JamfProvider('/nowhere.json'));

        $this->assertTrue($this->sync(new JamfProvider)->isFirstSync);
    }

    public function test_a_crash_halfway_saves_nothing_but_the_failed_attempt_is_kept(): void
    {
        $devices = [
            $this->device('SERIAL1'),
            $this->device('SERIAL2', str_repeat('x', 300)), // too long for the database
        ];

        $result = $this->sync($this->fakeProvider($devices));

        $this->assertSame(0, Asset::count());
        $run = SyncRun::sole();
        $this->assertSame(SyncRunStatus::Failed, $run->status);
        $this->assertSame('Something went wrong while saving. Nothing was changed. Try again later.', $run->message);
        $this->assertSame($result->syncRunId, $run->id);
    }

    public function test_skipped_entries_are_recorded(): void
    {
        $results = json_decode(file_get_contents(config('mdm.jamf.mock_path')), true)['results'];
        $results[3]['hardware']['serialNumber'] = '';
        $this->sync(new JamfProvider($this->tempFile(json_encode(['totalCount' => 7, 'results' => $results]))));

        $this->assertSame('no serial number', SyncRun::sole()->skipped[0]['reason']);
    }

    private function sync(MdmProvider $provider)
    {
        return app(MdmSyncService::class)->sync($provider);
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'jamf');
        file_put_contents($path, $contents);

        return $this->tempFiles[] = $path;
    }

    private function device(string $serial, string $deviceName = 'Test laptop'): DeviceData
    {
        return new DeviceData($serial, new OwnerData("{$serial}@company.test", $serial, null, null, null), $deviceName, null, null, [], null, null);
    }

    private function fakeProvider(array $devices): MdmProvider
    {
        return new class($devices) implements MdmProvider
        {
            public function __construct(private array $devices) {}

            public function name(): string
            {
                return 'jamf';
            }

            public function label(): string
            {
                return 'Jamf';
            }

            public function fetch(): array
            {
                return $this->devices;
            }

            public function mapper(): DeviceMapper
            {
                return new class implements DeviceMapper
                {
                    public function map(mixed $entry): DeviceData|SkippedDevice
                    {
                        return $entry;
                    }
                };
            }
        };
    }
}
