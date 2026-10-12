<?php

namespace Tests\Feature\Mdm;

use App\Enums\AssetStatus;
use App\Mdm\Contracts\DeviceMapper;
use App\Mdm\Contracts\MdmProvider;
use App\Mdm\Data\DeviceData;
use App\Mdm\Data\OwnerData;
use App\Mdm\Data\SkippedDevice;
use App\Mdm\Jamf\JamfProvider;
use App\Mdm\MdmSyncService;
use App\Mdm\SyncResult;
use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Sync engine scenarios. Test names start with their number in the test matrix
 * (context: decisions Part P) where one exists.
 */
class SyncEngineTest extends TestCase
{
    use RefreshDatabase;

    // Positions in the mock file
    private const ALEX_PRO = 0;      // C02DL0XYZQ6N, Alex

    private const EMMA_AIR = 1;      // FVFH90ABC123, Emma, 8 GB

    private const ALEX_SECOND = 2;   // C02INTEL9999, Alex

    private const LUCAS_PRO = 3;     // FVFUXDESIGN1, Lucas

    private const MIA_MINI = 4;      // MACMINIM1QA, Mia, no battery

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->tempFiles, is_file(...)));
        parent::tearDown();
    }

    public function test_t1_first_sync_imports_seven_laptops_and_six_people(): void
    {
        $result = $this->syncRealFile();

        $this->assertTrue($result->succeeded());
        $this->assertSame(7, Asset::count());
        $this->assertSame(6, Employee::count());
        $this->assertSame(7, Asset::where('status', AssetStatus::Assigned)->count());
        $this->assertSame(7, $result->counts['assets_created']);
        $this->assertSame(6, $result->counts['employees_created']);
        $this->assertSame([], $result->skipped);
    }

    public function test_t2_second_sync_of_the_same_list_changes_nothing(): void
    {
        $this->syncRealFile();
        $updatedAt = Asset::pluck('updated_at', 'serial_code');

        $this->travel(5)->minutes();
        $result = $this->syncRealFile();

        $this->assertSame(7, Asset::count());
        $this->assertSame(6, Employee::count());
        $this->assertSame(0, $result->counts['assets_created']);
        $this->assertSame(0, $result->counts['assets_updated']);
        $this->assertSame(7, $result->counts['assets_unchanged']);
        $this->assertSame(0, $result->counts['employees_updated']);
        $this->assertSame(6, $result->counts['employees_unchanged']);

        // Our own "seen" timestamps moved on, but they don't count as a change.
        $this->assertEquals($updatedAt, Asset::pluck('updated_at', 'serial_code'));
        $this->assertTrue(Asset::first()->last_seen_in_mdm_at->isAfter(now()->subMinute()));
    }

    public function test_t3_ram_change_is_reflected_and_counted_once(): void
    {
        $this->syncRealFile();

        $results = $this->mockResults();
        $results[self::EMMA_AIR]['hardware']['totalRamMegabytes'] = 16384;
        $result = $this->syncWith($results);

        $this->assertSame(16384, $this->asset('FVFH90ABC123')->attributes['ram_mb']);
        $this->assertSame(1, $result->counts['assets_updated']);
        $this->assertSame(6, $result->counts['assets_unchanged']);
    }

    public function test_t4_reassigned_laptop_follows_jamf(): void
    {
        $this->syncRealFile();

        $results = $this->mockResults();
        $results[self::ALEX_PRO]['userAndLocation'] = $results[self::EMMA_AIR]['userAndLocation'];
        $this->syncWith($results);

        $laptop = $this->asset('C02DL0XYZQ6N');
        $this->assertSame('emma.johnson@company.test', $laptop->employee->email);
        $this->assertSame('Emma Johnson', $laptop->last_owner_name);
        $this->assertSame('emma.johnson@company.test', $laptop->last_owner_email);
        $this->assertSame(AssetStatus::Assigned, $laptop->status);
    }

    public function test_t5_known_laptop_that_loses_its_owner_is_to_be_collected(): void
    {
        $this->syncRealFile();

        $result = $this->syncWith($this->withoutOwner(self::EMMA_AIR));

        $laptop = $this->asset('FVFH90ABC123');
        $this->assertSame(AssetStatus::ToBeCollected, $laptop->status);
        $this->assertNull($laptop->employee_id);
        $this->assertSame('Emma Johnson', $laptop->last_owner_name);
        $this->assertSame('emma.johnson@company.test', $laptop->last_owner_email);
        $this->assertNotNull(Employee::where('email', 'emma.johnson@company.test')->first());
        $this->assertSame(1, $result->counts['became_to_be_collected']);
    }

    public function test_to_be_collected_is_counted_only_when_the_status_changes(): void
    {
        $this->syncRealFile();
        $first = $this->syncWith($this->withoutOwner(self::EMMA_AIR));
        $second = $this->syncWith($this->withoutOwner(self::EMMA_AIR));

        $this->assertSame(1, $first->counts['became_to_be_collected']);
        $this->assertSame(0, $second->counts['became_to_be_collected']);
        $this->assertSame(7, $second->counts['assets_unchanged']);
        $this->assertSame(AssetStatus::ToBeCollected, $this->asset('FVFH90ABC123')->status);
    }

    public function test_t6_new_laptop_without_owner_is_skipped(): void
    {
        $result = $this->syncWith($this->withoutOwner(self::EMMA_AIR));

        $this->assertNull(Asset::where('serial_code', 'FVFH90ABC123')->first());
        $this->assertSame(6, Asset::count());
        $this->assertSame(
            [['serial' => 'FVFH90ABC123', 'external_id' => '102', 'reason' => 'no owner in Jamf']],
            $result->skipped,
        );
        $this->assertSame(1, $result->counts['skipped']);
    }

    public function test_t7_to_be_collected_laptop_gets_a_new_owner(): void
    {
        $this->syncRealFile();
        $this->syncWith($this->withoutOwner(self::EMMA_AIR));

        $results = $this->mockResults();
        $results[self::EMMA_AIR]['userAndLocation'] = $results[self::LUCAS_PRO]['userAndLocation'];
        $this->syncWith($results);

        $laptop = $this->asset('FVFH90ABC123');
        $this->assertSame(AssetStatus::Assigned, $laptop->status);
        $this->assertSame('lucas.brown@company.test', $laptop->employee->email);
        $this->assertSame('Lucas Brown', $laptop->last_owner_name);
    }

    public function test_t10_deleted_laptop_comes_back_on_the_next_sync(): void
    {
        $this->syncRealFile();
        $this->asset('C02DL0XYZQ6N')->delete();

        $result = $this->syncRealFile();

        $this->assertSame(7, Asset::count());
        $this->assertSame(1, $result->counts['assets_created']);
        $this->assertSame('alex.smith@company.test', $this->asset('C02DL0XYZQ6N')->employee->email);
    }

    public function test_t11_deleted_to_be_collected_laptop_does_not_come_back(): void
    {
        $this->syncRealFile();
        $this->syncWith($this->withoutOwner(self::EMMA_AIR));
        $this->asset('FVFH90ABC123')->delete();

        $result = $this->syncWith($this->withoutOwner(self::EMMA_AIR));

        $this->assertNull(Asset::where('serial_code', 'FVFH90ABC123')->first());
        $this->assertSame('no owner in Jamf', $result->skipped[0]['reason']);
    }

    public function test_t13_deleted_person_comes_back_with_their_laptops(): void
    {
        $this->syncRealFile();
        Employee::where('email', 'alex.smith@company.test')->first()->delete();

        $result = $this->syncRealFile();

        $alex = Employee::where('email', 'alex.smith@company.test')->first();
        $this->assertNotNull($alex);
        $this->assertSame(1, $result->counts['employees_created']);
        $this->assertEqualsCanonicalizing(['C02DL0XYZQ6N', 'C02INTEL9999'], $alex->assets->pluck('serial_code')->all());
    }

    public function test_t14_unreadable_list_changes_nothing(): void
    {
        $this->syncRealFile();
        $before = $this->snapshot();

        foreach (['{"results": [{"id": "101"', '{"totalCount": 0}', null] as $contents) {
            $provider = $contents === null ? new JamfProvider('/nowhere.json') : new JamfProvider($this->tempFile($contents));
            $result = app(MdmSyncService::class)->sync($provider);

            $this->assertFalse($result->succeeded());
            $this->assertSame("Couldn't read the Jamf list. Nothing was changed. Try again later.", $result->message);
            $this->assertSame($before, $this->snapshot());
        }
    }

    public function test_crash_halfway_saves_nothing(): void
    {
        $devices = [
            $this->device('SERIAL1', 'one@company.test'),
            $this->device('SERIAL2', 'two@company.test'),
            $this->device('SERIAL3', 'three@company.test', deviceName: str_repeat('x', 300)), // too long for the database
        ];

        $result = app(MdmSyncService::class)->sync($this->fakeProvider($devices));

        $this->assertFalse($result->succeeded());
        $this->assertSame('Something went wrong while saving. Nothing was changed. Try again later.', $result->message);
        $this->assertSame(0, Asset::count());
        $this->assertSame(0, Employee::count());
    }

    public function test_t17_phone_removed_in_jamf_is_removed_here(): void
    {
        $this->syncRealFile();

        $results = $this->mockResults();
        $results[self::ALEX_PRO]['userAndLocation']['phone'] = null;
        $results[self::ALEX_SECOND]['userAndLocation']['phone'] = null;
        $result = $this->syncWith($results);

        $this->assertNull(Employee::where('email', 'alex.smith@company.test')->first()->phone);
        $this->assertSame(1, $result->counts['employees_updated']);
    }

    public function test_t18_t19_missing_names_fall_back(): void
    {
        $results = $this->mockResults();
        $results[self::MIA_MINI]['userAndLocation']['realname'] = '';
        $results[self::MIA_MINI]['general']['name'] = null;
        $this->syncWith($results);

        $this->assertSame('mia.wilson@company.test', Employee::where('email', 'mia.wilson@company.test')->first()->name);
        $this->assertSame('Mac mini (M1, 2020)', $this->asset('MACMINIM1QA')->device_name);
    }

    public function test_t20_mac_mini_without_battery_is_stored_without_crashing(): void
    {
        $this->syncRealFile();

        $this->assertNull($this->asset('MACMINIM1QA')->attributes['battery_percent']);
    }

    public function test_t21_entry_without_serial_is_skipped_and_the_rest_synced(): void
    {
        $results = $this->mockResults();
        $results[self::LUCAS_PRO]['hardware']['serialNumber'] = '';
        $result = $this->syncWith($results);

        $this->assertSame(6, Asset::count());
        $this->assertSame([['serial' => null, 'external_id' => '104', 'reason' => 'no serial number']], $result->skipped);
    }

    public function test_t22_invalid_email_for_a_known_laptop_leaves_it_unchanged(): void
    {
        $this->syncRealFile();

        $results = $this->mockResults();
        $results[self::EMMA_AIR]['userAndLocation']['email'] = 'emma.johnson';
        $result = $this->syncWith($results);

        $laptop = $this->asset('FVFH90ABC123');
        $this->assertSame(AssetStatus::Assigned, $laptop->status);
        $this->assertSame('emma.johnson@company.test', $laptop->employee->email);
        $this->assertSame('invalid email', $result->skipped[0]['reason']);
        $this->assertSame(0, $result->counts['became_to_be_collected']);
    }

    public function test_t23_conflicting_duplicates_change_nothing_for_a_known_laptop(): void
    {
        $this->syncRealFile();
        $before = $this->snapshot();

        $result = $this->syncWith($this->withConflictingDuplicate());

        // Alex's laptop keeps everything it had; both entries are reported.
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(AssetStatus::Assigned, $this->asset('C02DL0XYZQ6N')->status);
        $this->assertSame([
            ['serial' => 'C02DL0XYZQ6N', 'external_id' => '101', 'reason' => 'listed twice with conflicting information'],
            ['serial' => 'C02DL0XYZQ6N', 'external_id' => '999', 'reason' => 'listed twice with conflicting information'],
        ], $result->skipped);
        $this->assertSame(0, $result->counts['assets_updated']);
        $this->assertSame(6, $result->counts['assets_unchanged']);
        $this->assertContains('C02DL0XYZQ6N', $result->seenSerials);
    }

    public function test_t23_conflicting_duplicates_are_not_imported_as_a_new_laptop(): void
    {
        $result = $this->syncWith($this->withConflictingDuplicate());

        $this->assertNull(Asset::where('serial_code', 'C02DL0XYZQ6N')->first());
        $this->assertSame(6, Asset::count());
        $this->assertCount(2, $result->skipped);
        $this->assertContains('C02DL0XYZQ6N', $result->seenSerials);
    }

    public function test_t23_identical_duplicates_are_used_normally(): void
    {
        $results = $this->mockResults();
        $results[] = $results[self::EMMA_AIR];
        $result = $this->syncWith($results);

        $this->assertSame(7, Asset::count());
        $this->assertSame(7, $result->counts['assets_created']);
        $this->assertSame([], $result->skipped);
        $this->assertSame('emma.johnson@company.test', $this->asset('FVFH90ABC123')->employee->email);
    }

    public function test_t23_duplicate_where_one_entry_is_broken_counts_as_conflicting(): void
    {
        $this->syncRealFile();

        $results = $this->mockResults();
        $broken = $results[self::EMMA_AIR];
        $broken['userAndLocation']['email'] = 'emma.johnson';
        $results[] = $broken;
        $result = $this->syncWith($results);

        $this->assertSame('emma.johnson@company.test', $this->asset('FVFH90ABC123')->employee->email);
        $this->assertSame(['listed twice with conflicting information', 'listed twice with conflicting information'], array_column($result->skipped, 'reason'));
    }

    public function test_skipped_entries_with_a_serial_count_as_seen(): void
    {
        $results = $this->mockResults();
        $results[self::EMMA_AIR]['userAndLocation']['email'] = 'emma.johnson';
        $results[self::LUCAS_PRO]['hardware']['serialNumber'] = '';
        $result = $this->syncWith($results);

        $this->assertContains('FVFH90ABC123', $result->seenSerials);   // invalid email: still seen
        $this->assertNotContains('FVFUXDESIGN1', $result->seenSerials); // no serial: can't be seen
        $this->assertCount(6, $result->seenSerials);
    }

    public function test_t24_messy_email_and_serial_match_existing_records(): void
    {
        $this->syncRealFile();

        $results = $this->mockResults();
        $results[self::EMMA_AIR]['userAndLocation']['email'] = '  Emma.Johnson@Company.TEST ';
        $results[self::EMMA_AIR]['hardware']['serialNumber'] = ' fvfh90abc123 ';
        $result = $this->syncWith($results);

        $this->assertSame(7, Asset::count());
        $this->assertSame(6, Employee::count());
        $this->assertSame(7, $result->counts['assets_unchanged']);
    }

    public function test_person_details_come_from_their_first_entry_in_the_list(): void
    {
        // Phone on the first entry, blank on the second: the phone is kept.
        $results = $this->mockResults();
        $results[self::ALEX_SECOND]['userAndLocation']['phone'] = null;
        $result = $this->syncWith($results);

        $this->assertSame('+491234567890', Employee::where('email', 'alex.smith@company.test')->first()->phone);
        $this->assertSame(6, $result->counts['employees_created']);

        // Blank on the first entry, phone on the second: the phone is empty.
        $results = $this->mockResults();
        $results[self::ALEX_PRO]['userAndLocation']['phone'] = null;
        $this->syncWith($results);

        $this->assertNull(Employee::where('email', 'alex.smith@company.test')->first()->phone);
    }

    public function test_engine_works_for_another_provider_with_its_own_wording(): void
    {
        $result = app(MdmSyncService::class)->sync($this->fakeProvider([
            $this->device('WIN-001', 'sam@company.test'),
            $this->device('WIN-002', null),
        ]));

        $this->assertTrue($result->succeeded());
        $this->assertSame('intune', $result->provider);
        $this->assertSame('intune', $this->asset('WIN-001')->provider);
        $this->assertSame('no owner in Intune', $result->skipped[0]['reason']);
    }

    public function test_sync_command_runs_against_the_configured_file(): void
    {
        $this->assertSame(0, Artisan::call('mdm:sync'));
        $this->assertStringContainsString('Sync from Jamf complete.', Artisan::output());
        $this->assertSame(7, Asset::count());
    }

    // Helpers

    private function syncRealFile(): SyncResult
    {
        return app(MdmSyncService::class)->sync(new JamfProvider);
    }

    /**
     * @param  list<array<string, mixed>>  $results
     */
    private function syncWith(array $results): SyncResult
    {
        $contents = json_encode(['totalCount' => count($results), 'results' => $results]);

        return app(MdmSyncService::class)->sync(new JamfProvider($this->tempFile($contents)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mockResults(): array
    {
        return json_decode(file_get_contents(config('mdm.jamf.mock_path')), true)['results'];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function withoutOwner(int $position): array
    {
        $results = $this->mockResults();
        $results[$position]['userAndLocation'] = array_fill_keys(array_keys($results[$position]['userAndLocation']), null);

        return $results;
    }

    /**
     * The mock list plus a second entry for Alex's laptop with different details.
     *
     * @return list<array<string, mixed>>
     */
    private function withConflictingDuplicate(): array
    {
        $results = $this->mockResults();
        $duplicate = $results[self::LUCAS_PRO];
        $duplicate['id'] = '999';
        $duplicate['hardware']['serialNumber'] = 'c02dl0xyzq6n'; // Alex's laptop, lower case
        $results[] = $duplicate;

        return $results;
    }

    private function asset(string $serial): Asset
    {
        return Asset::where('serial_code', $serial)->firstOrFail();
    }

    private function snapshot(): array
    {
        return [
            Asset::orderBy('id')->get()->map->only(['serial_code', 'status', 'employee_id', 'device_name', 'attributes'])->all(),
            Employee::orderBy('id')->get()->map->only(['email', 'name', 'phone'])->all(),
        ];
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'jamf');
        file_put_contents($path, $contents);

        return $this->tempFiles[] = $path;
    }

    private function device(string $serial, ?string $email, string $deviceName = 'Test laptop'): DeviceData
    {
        return new DeviceData(
            serial: $serial,
            owner: $email === null ? null : new OwnerData($email, ucfirst(strstr($email, '@', true)), null, null, null),
            deviceName: $deviceName,
            externalId: null,
            udid: null,
            attributes: ['model' => 'Test model'],
            lastContactAt: null,
            lastReportAt: null,
        );
    }

    /**
     * A made-up second MDM tool whose entries are already in the standard shape.
     *
     * @param  list<DeviceData>  $devices
     */
    private function fakeProvider(array $devices): MdmProvider
    {
        return new class($devices) implements MdmProvider
        {
            public function __construct(private array $devices) {}

            public function name(): string
            {
                return 'intune';
            }

            public function label(): string
            {
                return 'Intune';
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
