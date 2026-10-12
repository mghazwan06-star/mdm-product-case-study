<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\SyncRunStatus;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\SyncRun;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_email_can_only_be_stored_once(): void
    {
        Employee::create(['email' => 'alex.smith@company.test', 'name' => 'Alex Smith']);

        $this->expectException(UniqueConstraintViolationException::class);
        Employee::create(['email' => 'alex.smith@company.test', 'name' => 'Someone Else']);
    }

    public function test_email_uniqueness_ignores_upper_and_lower_case(): void
    {
        Employee::create(['email' => 'alex.smith@company.test', 'name' => 'Alex Smith']);

        $this->expectException(UniqueConstraintViolationException::class);
        Employee::create(['email' => 'Alex.Smith@Company.test', 'name' => 'Alex Smith']);
    }

    public function test_a_serial_can_only_be_stored_once_ignoring_case(): void
    {
        $this->makeAsset(['serial_code' => 'C02DL0XYZQ6N']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->makeAsset(['serial_code' => 'c02dl0xyzq6n']);
    }

    public function test_one_employee_can_have_several_laptops(): void
    {
        $alex = Employee::create(['email' => 'alex.smith@company.test', 'name' => 'Alex Smith']);
        $this->makeAsset(['serial_code' => 'C02DL0XYZQ6N', 'employee_id' => $alex->id]);
        $this->makeAsset(['serial_code' => 'C02INTEL9999', 'employee_id' => $alex->id]);

        $this->assertCount(2, $alex->assets);
    }

    public function test_deleting_an_employee_keeps_their_laptops_and_clears_the_link(): void
    {
        $alex = Employee::create(['email' => 'alex.smith@company.test', 'name' => 'Alex Smith']);
        $laptop = $this->makeAsset([
            'employee_id' => $alex->id,
            'last_owner_name' => 'Alex Smith',
            'last_owner_email' => 'alex.smith@company.test',
        ]);

        $alex->delete();

        $laptop->refresh();
        $this->assertNull($laptop->employee_id);
        $this->assertSame('Alex Smith', $laptop->last_owner_name);
        $this->assertSame('alex.smith@company.test', $laptop->last_owner_email);
    }

    public function test_a_laptop_stores_its_status_and_specs(): void
    {
        $laptop = $this->makeAsset([
            'status' => AssetStatus::ToBeCollected,
            'attributes' => ['make' => 'Apple', 'ram_mb' => 16384, 'battery_percent' => null],
        ]);

        $laptop = Asset::find($laptop->id);
        $this->assertSame(AssetStatus::ToBeCollected, $laptop->status);
        $this->assertSame(['make' => 'Apple', 'ram_mb' => 16384, 'battery_percent' => null], $laptop->attributes);
    }

    public function test_a_new_laptop_is_assigned_by_default(): void
    {
        $laptop = Asset::find($this->makeAsset()->id);

        $this->assertSame(AssetStatus::Assigned, $laptop->status);
    }

    public function test_a_sync_run_stores_its_result(): void
    {
        $run = SyncRun::create([
            'provider' => 'jamf',
            'status' => SyncRunStatus::Success,
            'started_at' => now(),
            'is_first_sync' => true,
            'counts' => ['assets_created' => 7, 'employees_created' => 6],
            'skipped' => [['serial' => null, 'reason' => 'no serial number']],
        ]);

        $run = SyncRun::find($run->id);
        $this->assertSame(SyncRunStatus::Success, $run->status);
        $this->assertTrue($run->is_first_sync);
        $this->assertSame(7, $run->counts['assets_created']);
        $this->assertSame('no serial number', $run->skipped[0]['reason']);
    }

    private function makeAsset(array $overrides = []): Asset
    {
        return Asset::create(array_merge([
            'serial_code' => 'C02DL0XYZQ6N',
            'provider' => 'jamf',
            'device_name' => 'Alex’s MacBook Pro',
        ], $overrides));
    }
}
