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
 * Odd but realistic Jamf data (docs/TEST-LOG.md, "Odd data", cases O1–O19).
 * Cases that showed a problem became findings (F1–F15); the tests for the
 * fixes Muhammad decided on are marked with the finding number.
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

    public function test_f1_long_text_is_stored_in_full_and_only_an_over_long_serial_is_skipped(): void
    {
        $long = str_repeat('Long name ', 30); // 300 characters
        $r = $this->results();
        $r[0]['general']['name'] = $long;
        $r[0]['userAndLocation']['realname'] = $long;
        $r[0]['userAndLocation']['position'] = $long;
        $r[3]['hardware']['serialNumber'] = str_repeat('X', 256);

        $result = $this->sync($r);

        $this->assertTrue($result->succeeded());
        $this->assertSame(trim($long), Asset::where('serial_code', 'C02DL0XYZQ6N')->value('device_name'));
        $person = Employee::where('email', 'alex.smith@company.test')->first();
        $this->assertSame(trim($long), $person->name);
        $this->assertSame(trim($long), $person->job_title);
        $this->assertSame([['serial' => null, 'external_id' => '104', 'reason' => 'serial number too long']], $result->skipped);
        $this->assertSame(6, Asset::count());
    }

    public function test_f5_a_serial_with_a_space_is_the_same_laptop(): void
    {
        $this->sync($this->results());

        $r = $this->results();
        $r[0]['hardware']['serialNumber'] = 'C02DL0 XYZQ6N';
        $result = $this->sync($r);

        $this->assertSame(7, Asset::count());
        $this->assertSame(0, Asset::where('status', AssetStatus::MissingFromMdm)->count());
        $this->assertSame(0, $result->counts['became_missing']);
    }

    public function test_f6_accented_emails_are_accepted_and_jose_and_josé_are_two_people(): void
    {
        $r = $this->results();
        $r[0]['userAndLocation']['email'] = 'josé@company.test';   // Alex's MacBook Pro
        $r[2]['userAndLocation']['email'] = 'JOSÉ@company.test';   // Alex's second laptop: same person
        $r[3]['userAndLocation']['email'] = 'jose@company.test';   // Lucas's laptop: a different person
        $r[4]['userAndLocation']['email'] = 'mia@exämple.com';     // accent after the @

        $result = $this->sync($r);

        $this->assertSame([], $result->skipped);
        $this->assertSame(2, Asset::where('last_owner_email', 'josé@company.test')->count());
        $this->assertSame(1, Asset::where('last_owner_email', 'jose@company.test')->count());
        $this->assertNotSame(
            Employee::where('email', 'josé@company.test')->value('id'),
            Employee::where('email', 'jose@company.test')->value('id'),
        );
        $this->assertNotNull(Employee::where('email', 'mia@exämple.com')->first());
    }

    public function test_f6_addresses_that_still_are_not_emails_are_skipped(): void
    {
        $r = $this->results();
        $r[0]['userAndLocation']['email'] = 'alex.smith';
        $r[1]['userAndLocation']['email'] = 'emma@company';
        $r[3]['userAndLocation']['email'] = 'lucas@@company.test';

        $result = $this->sync($r);

        $this->assertSame(['101' => 'invalid email', '102' => 'invalid email', '104' => 'invalid email'], array_column($result->skipped, 'reason', 'external_id'));
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
