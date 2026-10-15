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
        $this->assertSameFields(
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
        $r[5]['userAndLocation']['realname'] = 'سارة أحمد';
        $r[6]['general']['name'] = '<img src=x onerror=alert(1)>';

        $this->sync($r);

        $this->assertSame('Design 💻 MacBook', Asset::where('serial_code', 'FVFUXDESIGN1')->value('device_name'));
        $this->assertSame('سارة أحمد', Employee::where('email', 'noah.miller@company.test')->value('name'));
        $this->assertSame('<img src=x onerror=alert(1)>', Asset::where('serial_code', 'FINANCEPRO15')->value('device_name'));
    }

    public function test_o10_absurd_numbers_are_kept_and_too_big_ones_are_marked_unreadable(): void
    {
        $r = $this->results();
        $r[0]['hardware']['totalRamMegabytes'] = 999999999999;
        $r[2]['hardware']['totalRamMegabytes'] = 1.0E+30;

        $this->sync($r);

        $this->assertSame(999999999999, Asset::where('serial_code', 'C02DL0XYZQ6N')->first()->attributes['ram_mb']);
        $this->assertNull(Asset::where('serial_code', 'C02INTEL9999')->first()->attributes['ram_mb']);
        // F8: too big to use, so it's kept as Jamf sent it, not shown as "Not provided".
        $this->assertSame(['ram_mb' => '1.0e+30'], Asset::where('serial_code', 'C02INTEL9999')->first()->attributes['unreadable']);
    }

    public function test_o12_year_first_dates_are_read_and_other_values_are_marked_unreadable(): void
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

        // F7: a value Jamf sent but WorkWize can't use is kept as sent; an empty one is just missing.
        $unreadable = fn (string $serial) => Asset::where('serial_code', $serial)->first()->attributes['unreadable'] ?? [];
        $this->assertSame(['last_contact_at' => '1767609012'], $unreadable('FVFUXDESIGN1'));
        $this->assertSame([], $unreadable('AIRSUPPORT01'));
        $this->assertSame(['last_contact_at' => 'not a date'], $unreadable('FINANCEPRO15'));
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

    public function test_f3_numbers_sent_as_text_are_read_as_numbers(): void
    {
        $r = $this->results();
        $r[0]['hardware']['totalRamMegabytes'] = '16384';
        $r[0]['hardware']['coreCount'] = ' 10 ';
        $r[0]['hardware']['batteryCapacityPercent'] = '92';

        $this->sync($r);

        $specs = Asset::where('serial_code', 'C02DL0XYZQ6N')->first()->attributes;
        $this->assertSame([16384, 10, 92], [$specs['ram_mb'], $specs['cores'], $specs['battery_percent']]);
        $this->assertArrayNotHasKey('unreadable', $specs);
    }

    public function test_f7_dates_in_an_ambiguous_order_are_never_guessed(): void
    {
        $r = $this->results();
        $r[0]['general']['lastContactTime'] = '05/01/2026';
        $r[0]['purchasing']['poDate'] = '15/06/2022';
        $r[1]['general']['lastContactTime'] = '2026-02-30T10:00:00Z';  // no such day
        $r[2]['general']['lastContactTime'] = '2026-01-05T10:30:12.123Z';
        $r[2]['purchasing']['poDate'] = '2023-08-12T00:00:00Z';

        $this->sync($r);

        $alex = Asset::where('serial_code', 'C02DL0XYZQ6N')->first();
        $this->assertNull($alex->last_contact_at);
        $this->assertNull($alex->attributes['purchase_date']);
        $this->assertSameFields(['purchase_date' => '15/06/2022', 'last_contact_at' => '05/01/2026'], $alex->attributes['unreadable']);
        $this->assertSame(['last_contact_at' => '2026-02-30T10:00:00Z'], Asset::where('serial_code', 'FVFH90ABC123')->first()->attributes['unreadable']);

        $second = Asset::where('serial_code', 'C02INTEL9999')->first();
        $this->assertSame('2026-01-05T10:30:12+00:00', $second->last_contact_at->toIso8601String());
        $this->assertSame('2023-08-12', $second->attributes['purchase_date']);
        $this->assertArrayNotHasKey('unreadable', $second->attributes);
    }

    public function test_f8_impossible_values_are_shown_as_sent_and_missing_ones_stay_missing(): void
    {
        $r = $this->results();
        $r[0]['hardware']['batteryCapacityPercent'] = 140;
        $r[0]['hardware']['totalRamMegabytes'] = -8192;
        $r[0]['hardware']['coreCount'] = 0;
        $r[0]['purchasing']['purchased'] = 'yes';
        $r[1]['hardware']['batteryCapacityPercent'] = null;

        $this->sync($r);

        $alex = Asset::where('serial_code', 'C02DL0XYZQ6N')->first();
        $this->assertSameFields(
            ['cores' => '0', 'ram_mb' => '-8192', 'battery_percent' => '140', 'purchased' => 'yes'],
            $alex->attributes['unreadable'],
        );
        $this->assertNull($alex->attributes['battery_percent']);
        $this->assertArrayNotHasKey('unreadable', Asset::where('serial_code', 'FVFH90ABC123')->first()->attributes);

        // The laptop page gets what Jamf sent.
        $this->getJson("/api/assets/{$alex->id}")->assertJsonPath('data.attributes.unreadable.battery_percent', '140');
    }

    public function test_f7_every_date_in_workwizes_real_mock_still_reads_the_same(): void
    {
        $this->sync($this->results());

        $expected = [
            'C02DL0XYZQ6N' => ['2026-01-05T10:30:12Z', '2025-11-20T09:15:00Z', '2022-06-15'],
            'FVFH90ABC123' => ['2026-01-04T21:11:55Z', '2025-12-02T14:45:10Z', '2023-02-01'],
            'C02INTEL9999' => ['2026-01-05T09:20:00Z', '2025-12-10T10:00:00Z', '2023-08-12'],
            'FVFUXDESIGN1' => ['2026-01-03T18:10:00Z', '2025-11-18T08:40:00Z', '2022-11-05'],
            'MACMINIM1QA' => ['2026-01-02T16:45:00Z', '2025-10-01T11:25:00Z', '2021-05-20'],
            'AIRSUPPORT01' => ['2026-01-01T13:05:00Z', '2025-09-12T07:55:00Z', '2022-01-10'],
            'FINANCEPRO15' => ['2025-12-28T19:30:00Z', '2025-08-05T09:10:00Z', '2020-03-15'],
        ];

        foreach ($expected as $serial => [$contact, $report, $purchase]) {
            $asset = Asset::where('serial_code', $serial)->first();
            $this->assertSame($contact, $asset->last_contact_at->toIso8601ZuluString());
            $this->assertSame($report, $asset->last_report_at->toIso8601ZuluString());
            $this->assertSame($purchase, $asset->attributes['purchase_date']);
            $this->assertArrayNotHasKey('unreadable', $asset->attributes);
        }
    }

    /**
     * Same fields with the same values, in any order (MySQL reorders the keys of stored JSON).
     */
    private function assertSameFields(array $expected, array $actual): void
    {
        ksort($expected);
        ksort($actual);
        $this->assertSame($expected, $actual);
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
