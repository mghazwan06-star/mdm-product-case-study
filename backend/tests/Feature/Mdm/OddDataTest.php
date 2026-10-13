<?php

namespace Tests\Feature\Mdm;

use App\Enums\AssetStatus;
use App\Mdm\Jamf\JamfProvider;
use App\Mdm\MdmSyncService;
use App\Mdm\SyncResult;
use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Odd but realistic Jamf data that WorkWize already handles correctly
 * (docs/TEST-LOG.md, "Odd data", cases O5–O17). Cases that showed a problem
 * are findings, not tests, until a decision is made.
 */
class OddDataTest extends TestCase
{
    use RefreshDatabase;

    // Mock positions: 0 Alex Pro, 1 Emma Air, 2 Alex second, 3 Lucas, 4 Mia mini, 5 Noah, 6 Olivia

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->tempFiles, is_file(...)));
        parent::tearDown();
    }

    public function test_o5_an_email_alias_with_plus_works_and_a_space_inside_is_skipped(): void
    {
        $r = $this->results();
        $r[4]['userAndLocation']['email'] = 'mia+laptop@company.test';
        $r[5]['userAndLocation']['email'] = 'noah miller@company.test';

        $result = $this->sync($r);

        $this->assertNotNull(Employee::where('email', 'mia+laptop@company.test')->first());
        $this->assertSame([['serial' => 'AIRSUPPORT01', 'external_id' => '106', 'reason' => 'invalid email']], $result->skipped);
    }

    public function test_o6_missing_or_malformed_sections_are_skipped_or_fall_back_without_crashing(): void
    {
        $r = $this->results();
        unset($r[3]['hardware']);
        unset($r[4]['general']);
        unset($r[5]['userAndLocation']);
        $r[6]['general'] = null;
        $r[1]['hardware'] = 'MacBook Air';

        $result = $this->sync($r);

        $this->assertTrue($result->succeeded());
        $this->assertEqualsCanonicalizing(
            ['102' => 'incomplete information', '104' => 'no serial number', '106' => 'no owner in Jamf'],
            array_column($result->skipped, 'reason', 'external_id'),
        );
        $this->assertSame('Mac mini (M1, 2020)', Asset::where('serial_code', 'MACMINIM1QA')->value('device_name'));
        $this->assertSame('MacBook Pro (15-inch, 2018)', Asset::where('serial_code', 'FINANCEPRO15')->value('device_name'));
    }

    public function test_o7_o8_o9_emoji_right_to_left_text_and_web_code_are_stored_exactly_as_sent(): void
    {
        $r = $this->results();
        $r[3]['general']['name'] = 'Design 💻 MacBook';
        $r[5]['userAndLocation']['realname'] = 'محمد غزوان';
        $r[6]['general']['name'] = '<img src=x onerror=alert(1)>';

        $this->sync($r);

        $this->assertSame('Design 💻 MacBook', Asset::where('serial_code', 'FVFUXDESIGN1')->value('device_name'));
        $this->assertSame('محمد غزوان', Employee::where('email', 'noah.miller@company.test')->value('name'));
        $this->assertSame('<img src=x onerror=alert(1)>', Asset::where('serial_code', 'FINANCEPRO15')->value('device_name'));
    }

    public function test_o10_absurd_numbers_are_kept_and_too_big_ones_become_empty(): void
    {
        $r = $this->results();
        $r[0]['hardware']['totalRamMegabytes'] = 999999999999;
        $r[2]['hardware']['totalRamMegabytes'] = 1.0E+30;

        $this->sync($r);

        $this->assertSame(999999999999, Asset::where('serial_code', 'C02DL0XYZQ6N')->first()->attributes['ram_mb']);
        $this->assertNull(Asset::where('serial_code', 'C02INTEL9999')->first()->attributes['ram_mb']);
    }

    public function test_o12_common_date_formats_are_read_and_unreadable_ones_become_empty(): void
    {
        $r = $this->results();
        $r[0]['general']['lastContactTime'] = '2026-01-05 10:30:12';
        $r[1]['general']['lastContactTime'] = '2026-01-05';
        $r[2]['general']['lastContactTime'] = '2026-01-05T12:30:12+02:00';
        $r[3]['general']['lastContactTime'] = 1767609012;
        $r[5]['general']['lastContactTime'] = '';
        $r[6]['general']['lastContactTime'] = 'not a date';

        $this->sync($r);

        $contact = fn (string $serial) => Asset::where('serial_code', $serial)->first()->last_contact_at?->toIso8601String();
        $this->assertSame('2026-01-05T10:30:12+00:00', $contact('C02DL0XYZQ6N'));
        $this->assertSame('2026-01-05T00:00:00+00:00', $contact('FVFH90ABC123'));
        $this->assertSame('2026-01-05T10:30:12+00:00', $contact('C02INTEL9999'));
        $this->assertNull($contact('FVFUXDESIGN1'));
        $this->assertNull($contact('AIRSUPPORT01'));
        $this->assertNull($contact('FINANCEPRO15'));
    }

    public function test_o14_o15_a_numeric_jamf_id_and_unknown_fields_are_fine(): void
    {
        $r = $this->results();
        $r[0]['id'] = 101;
        $r[0]['extensionAttributes'] = [['name' => 'Cost centre', 'value' => 'R&D']];

        $result = $this->sync($r);

        $this->assertTrue($result->succeeded());
        $this->assertSame('101', Asset::where('serial_code', 'C02DL0XYZQ6N')->value('external_id'));
    }

    public function test_o16_the_same_email_in_other_capitals_is_one_person_with_the_first_entrys_name(): void
    {
        $r = $this->results();
        $r[2]['userAndLocation']['email'] = 'ALEX.SMITH@Company.TEST';
        $r[2]['userAndLocation']['realname'] = 'Alexander Smith';

        $this->sync($r);

        $this->assertSame(6, Employee::count());
        $this->assertSame('Alex Smith', Employee::where('email', 'alex.smith@company.test')->value('name'));
        $this->assertSame(2, Asset::where('status', AssetStatus::Assigned)->where('last_owner_email', 'alex.smith@company.test')->count());
    }

    public function test_o17_names_that_are_only_spaces_fall_back(): void
    {
        $r = $this->results();
        $r[4]['userAndLocation']['realname'] = '   ';
        $r[4]['general']['name'] = '   ';

        $this->sync($r);

        $this->assertSame('mia.wilson@company.test', Employee::where('email', 'mia.wilson@company.test')->value('name'));
        $this->assertSame('Mac mini (M1, 2020)', Asset::where('serial_code', 'MACMINIM1QA')->value('device_name'));
    }

    private function results(): array
    {
        return json_decode(file_get_contents(config('mdm.jamf.mock_path')), true)['results'];
    }

    private function sync(array $results): SyncResult
    {
        $path = tempnam(sys_get_temp_dir(), 'jamf');
        file_put_contents($path, json_encode(['totalCount' => count($results), 'results' => $results], JSON_UNESCAPED_UNICODE));
        $this->tempFiles[] = $path;

        return app(MdmSyncService::class)->sync(new JamfProvider($path));
    }
}
