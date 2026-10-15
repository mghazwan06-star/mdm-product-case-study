<?php

namespace Tests\Unit\Mdm;

use App\Mdm\Data\DeviceData;
use App\Mdm\Data\SkippedDevice;
use App\Mdm\Data\SkipReason;
use App\Mdm\Jamf\JamfDeviceMapper;
use PHPUnit\Framework\TestCase;

class JamfDeviceMapperTest extends TestCase
{
    private JamfDeviceMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new JamfDeviceMapper;
    }

    // The 7 real laptops from the mock file

    public function test_all_seven_mock_laptops_translate_without_skips(): void
    {
        $devices = array_map($this->mapper->map(...), $this->mockResults());

        $this->assertCount(7, $devices);
        $this->assertContainsOnlyInstancesOf(DeviceData::class, $devices);
    }

    public function test_alex_macbook_pro_is_translated_field_by_field(): void
    {
        $device = $this->mapper->map($this->mockResults()[0]);

        $this->assertSame('C02DL0XYZQ6N', $device->serial);
        $this->assertSame('Alex’s MacBook Pro', $device->deviceName);
        $this->assertSame('101', $device->externalId);
        $this->assertSame('6E662F59-DF86-41CE-BD4C-4AD8CE32369C', $device->udid);

        $this->assertSame('alex.smith@company.test', $device->owner->email);
        $this->assertSame('Alex Smith', $device->owner->name);
        $this->assertSame('Backend Engineer', $device->owner->jobTitle);
        $this->assertSame('+491234567890', $device->owner->phone);
        $this->assertSame('alex.smith', $device->owner->username);

        $this->assertSame([
            'make' => 'Apple',
            'model' => 'MacBook Pro (14-inch, M1 Pro, 2021)',
            'model_identifier' => 'MacBookPro18,3',
            'processor' => 'Apple M1 Pro',
            'cores' => 10,
            'ram_mb' => 16384,
            'battery_percent' => 91,
            'platform' => 'Mac',
            'site' => 'None',
            'purchased' => true,
            'purchase_date' => '2022-06-15',
        ], $device->attributes);

        $this->assertSame('2026-01-05T10:30:12+00:00', $device->lastContactAt->toIso8601String());
        $this->assertSame('2025-11-20T09:15:00+00:00', $device->lastReportAt->toIso8601String());
    }

    public function test_alex_two_laptops_have_the_same_owner_email(): void
    {
        $results = $this->mockResults();

        $this->assertSame(
            $this->mapper->map($results[0])->owner->email,
            $this->mapper->map($results[2])->owner->email,
        );
    }

    public function test_emma_missing_phone_becomes_empty(): void
    {
        $this->assertNull($this->mapper->map($this->mockResults()[1])->owner->phone);
    }

    public function test_mac_mini_missing_battery_becomes_empty(): void
    {
        $device = $this->mapper->map($this->mockResults()[4]);

        $this->assertSame('QA Mac Mini', $device->deviceName);
        $this->assertNull($device->attributes['battery_percent']);
    }

    // Made-up edge cases

    public function test_messy_email_and_serial_are_tidied(): void
    {
        $device = $this->mapper->map($this->entry(email: '  Alex.Smith@Company.TEST ', serial: ' c02dl0xyzq6n '));

        $this->assertSame('alex.smith@company.test', $device->owner->email);
        $this->assertSame('C02DL0XYZQ6N', $device->serial);
    }

    public function test_blank_person_name_falls_back_to_email(): void
    {
        $device = $this->mapper->map($this->entry(realname: '  '));

        $this->assertSame('alex.smith@company.test', $device->owner->name);
    }

    public function test_blank_laptop_name_falls_back_to_model(): void
    {
        $device = $this->mapper->map($this->entry(name: ''));

        $this->assertSame('MacBook Air (M2, 2022)', $device->deviceName);
    }

    public function test_blank_laptop_name_and_model_fall_back_to_unknown_device(): void
    {
        $device = $this->mapper->map($this->entry(name: null, model: null));

        $this->assertSame('Unknown device (SERIAL123)', $device->deviceName);
    }

    public function test_blank_email_means_no_owner_not_a_skip(): void
    {
        foreach ([null, '', '   '] as $email) {
            $device = $this->mapper->map($this->entry(email: $email));

            $this->assertInstanceOf(DeviceData::class, $device);
            $this->assertNull($device->owner);
        }
    }

    public function test_invalid_email_is_skipped_and_keeps_the_serial(): void
    {
        $skipped = $this->mapper->map($this->entry(email: 'alex.smith'));

        $this->assertInstanceOf(SkippedDevice::class, $skipped);
        $this->assertSame(SkipReason::InvalidEmail, $skipped->reason);
        $this->assertSame('SERIAL123', $skipped->serial);
        $this->assertTrue($skipped->countsAsSeen());
    }

    public function test_missing_serial_is_skipped(): void
    {
        foreach ([null, '', '  '] as $serial) {
            $skipped = $this->mapper->map($this->entry(serial: $serial));

            $this->assertInstanceOf(SkippedDevice::class, $skipped);
            $this->assertSame(SkipReason::NoSerial, $skipped->reason);
            $this->assertSame('900', $skipped->externalId);
            $this->assertFalse($skipped->countsAsSeen());
        }
    }

    public function test_entry_that_is_not_a_laptop_record_is_skipped_as_incomplete(): void
    {
        foreach ([42, 'laptop', null, ['a', 'b']] as $entry) {
            $skipped = $this->mapper->map($entry);

            $this->assertInstanceOf(SkippedDevice::class, $skipped);
            $this->assertSame(SkipReason::IncompleteInformation, $skipped->reason);
        }
    }

    public function test_section_with_the_wrong_shape_is_incomplete_and_keeps_the_serial(): void
    {
        $entry = $this->entry();
        $entry['userAndLocation'] = 'alex.smith@company.test';

        $skipped = $this->mapper->map($entry);

        $this->assertSame(SkipReason::IncompleteInformation, $skipped->reason);
        $this->assertSame('SERIAL123', $skipped->serial);
        $this->assertTrue($skipped->countsAsSeen());
    }

    public function test_nonsense_numbers_and_dates_are_kept_as_sent_not_used(): void
    {
        $entry = $this->entry();
        $entry['hardware']['totalRamMegabytes'] = 'lots';
        $entry['hardware']['coreCount'] = -2;
        $entry['hardware']['batteryCapacityPercent'] = 140;
        $entry['purchasing']['poDate'] = '2023-02-31';
        $entry['general']['lastContactTime'] = 'yesterday-ish';

        $device = $this->mapper->map($entry);

        $this->assertNull($device->attributes['ram_mb']);
        $this->assertNull($device->attributes['cores']);
        $this->assertNull($device->attributes['battery_percent']);
        $this->assertNull($device->attributes['purchase_date']);
        $this->assertNull($device->lastContactAt);
        // Not used, but kept as Jamf sent them, so the laptop page can say so (F3, F7, F8).
        $this->assertSame([
            'cores' => '-2',
            'ram_mb' => 'lots',
            'battery_percent' => '140',
            'purchase_date' => '2023-02-31',
            'last_contact_at' => 'yesterday-ish',
        ], $device->attributes['unreadable']);
    }

    public function test_empty_phone_becomes_empty(): void
    {
        $this->assertNull($this->mapper->map($this->entry(phone: ''))->owner->phone);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mockResults(): array
    {
        $path = dirname(__DIR__, 3).'/../files/api-mock-response.json';

        return json_decode(file_get_contents($path), true)['results'];
    }

    private function entry(
        ?string $email = 'alex.smith@company.test',
        ?string $serial = 'SERIAL123',
        ?string $realname = 'Alex Smith',
        ?string $name = 'Test MacBook',
        ?string $model = 'MacBook Air (M2, 2022)',
        ?string $phone = '+491234567890',
    ): array {
        return [
            'id' => '900',
            'udid' => 'TEST-UDID',
            'general' => [
                'name' => $name,
                'platform' => 'Mac',
                'reportDate' => '2025-12-02T14:45:10Z',
                'lastContactTime' => '2026-01-04T21:11:55Z',
                'site' => ['id' => '-1', 'name' => 'None'],
            ],
            'purchasing' => ['purchased' => true, 'poDate' => '2023-02-01'],
            'userAndLocation' => [
                'username' => 'alex.smith',
                'realname' => $realname,
                'email' => $email,
                'position' => 'Backend Engineer',
                'phone' => $phone,
            ],
            'hardware' => [
                'make' => 'Apple',
                'model' => $model,
                'modelIdentifier' => 'MacBookAir14,2',
                'serialNumber' => $serial,
                'processorType' => 'Apple M2',
                'coreCount' => 8,
                'totalRamMegabytes' => 8192,
                'batteryCapacityPercent' => 88,
            ],
        ];
    }
}
