<?php

namespace Tests\Feature\Api;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The doorways the website uses. Test names starting with t1, t2, … match the
 * numbered scenarios in docs/TEST-LOG.md.
 */
class ApiTest extends TestCase
{
    use RefreshDatabase;

    private const NOT_EDITABLE = "This action isn't available. WorkWize copies data from Jamf, so records can be deleted here but not edited.";

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->tempFiles, is_file(...)));
        parent::tearDown();
    }

    // Sync

    public function test_t1_t2_sync_button_imports_then_reports_unchanged(): void
    {
        $this->postJson('/api/sync')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('is_first_sync', true)
            ->assertJsonPath('provider', 'jamf')
            ->assertJsonPath('counts.assets_created', 7)
            ->assertJsonPath('counts.employees_created', 6)
            ->assertJsonPath('needs_attention', [])
            ->assertJsonPath('skipped', []);

        $this->postJson('/api/sync')
            ->assertOk()
            ->assertJsonPath('is_first_sync', false)
            ->assertJsonPath('counts.assets_unchanged', 7)
            ->assertJsonPath('counts.assets_updated', 0);
    }

    public function test_t14_sync_with_a_broken_file_returns_the_plain_message_and_changes_nothing(): void
    {
        $this->postJson('/api/sync');
        config(['mdm.jamf.mock_path' => $this->tempFile('{"results": [')]);

        $this->postJson('/api/sync')
            ->assertStatus(503)
            ->assertJsonPath('status', 'failed')
            ->assertJsonPath('message', "Couldn't read the Jamf list. Nothing was changed. Try again later.")
            ->assertJsonPath('error', 'Jamf file is not valid JSON.');

        $this->assertSame(7, Asset::count());
        $this->assertSame(6, Employee::count());
    }

    // Laptops

    public function test_laptops_list_shows_every_laptop_with_its_owner(): void
    {
        $this->postJson('/api/sync');

        $response = $this->getJson('/api/assets')->assertOk()->assertJsonCount(7, 'data');

        $alexPro = collect($response->json('data'))->firstWhere('serial_code', 'C02DL0XYZQ6N');
        $this->assertSame('Alex’s MacBook Pro', $alexPro['device_name']);
        $this->assertSame('assigned', $alexPro['status']);
        $this->assertSame('Alex Smith', $alexPro['owner']['name']);
        $this->assertSame('Alex Smith', $alexPro['last_owner_name']);
        $this->assertSame(
            ['id', 'device_name', 'serial_code', 'status', 'owner', 'last_owner_name'],
            array_keys($alexPro),
        );
    }

    public function test_t28_laptops_list_counts_and_filters_the_ones_that_need_attention(): void
    {
        $this->postJson('/api/sync');
        $this->getJson('/api/assets')->assertJsonPath('meta.needs_attention_count', 0);

        Asset::where('serial_code', 'FVFH90ABC123')->update(['status' => AssetStatus::ToBeCollected, 'employee_id' => null]);
        Asset::where('serial_code', 'FVFUXDESIGN1')->update(['status' => AssetStatus::MissingFromMdm, 'employee_id' => null]);
        Asset::where('serial_code', 'MACMINIM1QA')->update(['status' => AssetStatus::OwnerRemovedLocally, 'employee_id' => null]);

        $this->getJson('/api/assets')
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('meta.needs_attention_count', 3);

        $filtered = $this->getJson('/api/assets?needs_attention=1')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.needs_attention_count', 3);
        $this->assertEqualsCanonicalizing(
            ['FVFH90ABC123', 'FVFUXDESIGN1', 'MACMINIM1QA'],
            array_column($filtered->json('data'), 'serial_code'),
        );
    }

    public function test_laptop_details_show_every_field(): void
    {
        $this->postJson('/api/sync');
        $mini = Asset::where('serial_code', 'MACMINIM1QA')->first();

        $this->getJson("/api/assets/{$mini->id}")
            ->assertOk()
            ->assertJsonPath('data.device_name', 'QA Mac Mini')
            ->assertJsonPath('data.owner.email', 'mia.wilson@company.test')
            ->assertJsonPath('data.owner.job_title', 'QA Engineer')
            ->assertJsonPath('data.owner.phone', '+441234567890')
            ->assertJsonPath('data.attributes.processor', 'Apple M1')
            ->assertJsonPath('data.attributes.ram_mb', 16384)
            ->assertJsonPath('data.attributes.battery_percent', null)
            ->assertJsonPath('data.attributes.purchase_date', '2021-05-20')
            ->assertJsonPath('data.provider', 'jamf')
            ->assertJsonPath('data.external_id', '105')
            ->assertJsonPath('data.last_contact_at', '2026-01-02T16:45:00+00:00');
    }

    public function test_unknown_laptop_or_person_gets_a_plain_message(): void
    {
        $this->getJson('/api/assets/999')->assertNotFound()
            ->assertExactJson(['message' => "This laptop doesn't exist. It may have been deleted."]);
        $this->deleteJson('/api/assets/999')->assertNotFound()
            ->assertExactJson(['message' => "This laptop doesn't exist. It may have been deleted."]);
        $this->deleteJson('/api/employees/999')->assertNotFound()
            ->assertExactJson(['message' => "This person doesn't exist. They may have been deleted."]);
    }

    public function test_t10_deleted_laptop_is_gone_then_returns_on_the_next_sync(): void
    {
        $this->postJson('/api/sync');
        $laptop = Asset::where('serial_code', 'C02DL0XYZQ6N')->first();

        $this->deleteJson("/api/assets/{$laptop->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Alex’s MacBook Pro was deleted from WorkWize.')
            ->assertJsonPath('returns_on_next_sync', true);
        $this->getJson("/api/assets/{$laptop->id}")->assertNotFound();

        $this->postJson('/api/sync')->assertJsonPath('counts.assets_created', 1);
        $this->assertSame(7, Asset::count());
    }

    public function test_deleting_a_to_be_collected_laptop_says_it_will_not_return(): void
    {
        $this->postJson('/api/sync');
        $laptop = Asset::where('serial_code', 'FVFH90ABC123')->first();
        $laptop->update(['status' => AssetStatus::ToBeCollected, 'employee_id' => null]);

        $this->deleteJson("/api/assets/{$laptop->id}")->assertJsonPath('returns_on_next_sync', false);
    }

    // People

    public function test_people_list_shows_how_many_laptops_each_person_has(): void
    {
        $this->postJson('/api/sync');

        $people = collect($this->getJson('/api/employees')->assertOk()->assertJsonCount(6, 'data')->json('data'));

        $this->assertSame(2, $people->firstWhere('email', 'alex.smith@company.test')['assets_count']);
        $this->assertSame(1, $people->firstWhere('email', 'emma.johnson@company.test')['assets_count']);
        $this->assertSame('Backend Engineer', $people->firstWhere('email', 'alex.smith@company.test')['job_title']);
    }

    public function test_t12_deleting_a_person_keeps_their_laptops_as_owner_removed(): void
    {
        $this->postJson('/api/sync');
        $alex = Employee::where('email', 'alex.smith@company.test')->first();

        $this->deleteJson("/api/employees/{$alex->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Alex Smith was deleted from WorkWize.')
            ->assertJsonPath('laptops_affected', 2);

        $this->assertNull(Employee::find($alex->id));
        foreach (['C02DL0XYZQ6N', 'C02INTEL9999'] as $serial) {
            $laptop = Asset::where('serial_code', $serial)->first();
            $this->assertSame(AssetStatus::OwnerRemovedLocally, $laptop->status);
            $this->assertNull($laptop->employee_id);
            $this->assertSame('Alex Smith', $laptop->last_owner_name);
        }
        $this->assertSame(0, Asset::where('status', AssetStatus::ToBeCollected)->count());
    }

    public function test_t13_sync_after_deleting_a_person_brings_them_back_with_their_laptops(): void
    {
        $this->postJson('/api/sync');
        $this->deleteJson('/api/employees/'.Employee::where('email', 'alex.smith@company.test')->value('id'));

        $this->postJson('/api/sync')->assertJsonPath('counts.employees_created', 1);

        $alex = Employee::where('email', 'alex.smith@company.test')->first();
        $this->assertSame(2, $alex->assets()->where('status', AssetStatus::Assigned)->count());
        $this->assertSame(0, Asset::where('status', AssetStatus::OwnerRemovedLocally)->count());
    }

    public function test_deleting_a_person_without_laptops(): void
    {
        $person = Employee::create(['email' => 'sam@company.test', 'name' => 'Sam']);

        $this->deleteJson("/api/employees/{$person->id}")->assertOk()->assertJsonPath('laptops_affected', 0);
        $this->assertNull(Employee::find($person->id));
    }

    public function test_deleting_a_person_leaves_their_to_be_collected_laptop_as_it_is(): void
    {
        $this->postJson('/api/sync');
        $emma = Employee::where('email', 'emma.johnson@company.test')->first();
        $laptop = Asset::where('serial_code', 'FVFH90ABC123')->first();
        $laptop->update(['status' => AssetStatus::ToBeCollected, 'employee_id' => null]);

        $this->deleteJson("/api/employees/{$emma->id}")->assertJsonPath('laptops_affected', 0);

        $laptop->refresh();
        $this->assertSame(AssetStatus::ToBeCollected, $laptop->status);
        $this->assertSame('Emma Johnson', $laptop->last_owner_name);
    }

    // No editing anywhere

    public function test_editing_or_creating_records_is_refused_with_a_plain_message(): void
    {
        $this->postJson('/api/sync');
        $laptopId = Asset::value('id');
        $personId = Employee::value('id');

        $attempts = [
            ['putJson', "/api/assets/{$laptopId}"],
            ['patchJson', "/api/assets/{$laptopId}"],
            ['postJson', '/api/assets'],
            ['putJson', "/api/employees/{$personId}"],
            ['patchJson', "/api/employees/{$personId}"],
            ['postJson', '/api/employees'],
        ];

        foreach ($attempts as [$method, $url]) {
            $this->{$method}($url, ['device_name' => 'Changed', 'name' => 'Changed'])
                ->assertStatus(405)
                ->assertExactJson(['message' => self::NOT_EDITABLE]);
        }

        $this->assertSame(0, Asset::where('device_name', 'Changed')->count());
        $this->assertSame(0, Employee::where('name', 'Changed')->count());
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'jamf');
        file_put_contents($path, $contents);

        return $this->tempFiles[] = $path;
    }
}
