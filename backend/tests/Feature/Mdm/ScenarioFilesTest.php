<?php

namespace Tests\Feature\Mdm;

use App\Enums\AssetStatus;
use App\Mdm\Jamf\JamfProvider;
use App\Mdm\MdmSyncService;
use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The scenario files used for manual checks do what their README says.
 */
class ScenarioFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_emma_leaves_marks_her_laptop_to_be_collected(): void
    {
        $this->sync(config('mdm.jamf.mock_path'));

        $result = $this->sync($this->scenario('emma-leaves.json'));

        $this->assertTrue($result->succeeded());
        $this->assertSame(1, $result->counts['became_to_be_collected']);
        $laptop = Asset::where('serial_code', 'FVFH90ABC123')->first();
        $this->assertSame(AssetStatus::ToBeCollected, $laptop->status);
        $this->assertSame('Emma Johnson', $laptop->last_owner_name);
    }

    public function test_eventful_gives_one_laptop_to_collect_and_one_no_longer_in_jamf(): void
    {
        $this->sync(config('mdm.jamf.mock_path'));

        $result = $this->sync($this->scenario('eventful.json'));

        $this->assertSame(
            ['to_be_collected' => 'Emma Johnson', 'missing_from_mdm' => 'Lucas Brown'],
            array_column($result->needsAttention, 'person_name', 'status'),
        );
    }

    public function test_empty_list_flags_every_laptop_and_deletes_nothing(): void
    {
        $this->sync(config('mdm.jamf.mock_path'));

        $result = $this->sync($this->scenario('empty.json'));

        $this->assertTrue($result->succeeded());
        $this->assertSame(7, Asset::count());
        $this->assertSame(7, Asset::where('status', AssetStatus::MissingFromMdm)->count());
    }

    public function test_broken_file_is_rejected(): void
    {
        $result = $this->sync($this->scenario('broken.json'));

        $this->assertFalse($result->succeeded());
        $this->assertSame('Jamf file is not valid JSON.', $result->error);
    }

    private function sync(string $path)
    {
        return app(MdmSyncService::class)->sync(new JamfProvider($path));
    }

    private function scenario(string $file): string
    {
        return base_path("tests/Fixtures/jamf/{$file}");
    }
}
