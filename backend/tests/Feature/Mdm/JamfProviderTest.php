<?php

namespace Tests\Feature\Mdm;

use App\Mdm\Contracts\MdmProvider;
use App\Mdm\Exceptions\UnreadableSourceException;
use App\Mdm\Jamf\JamfDeviceMapper;
use App\Mdm\Jamf\JamfProvider;
use Tests\TestCase;

class JamfProviderTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        array_map(unlink(...), array_filter($this->tempFiles, is_file(...)));
        parent::tearDown();
    }

    public function test_the_real_mock_file_hands_over_seven_laptops(): void
    {
        $provider = new JamfProvider;

        $this->assertSame('jamf', $provider->name());
        $this->assertCount(7, $provider->fetch());
        $this->assertInstanceOf(JamfDeviceMapper::class, $provider->mapper());
    }

    public function test_jamf_is_listed_as_an_available_provider(): void
    {
        $provider = app(config('mdm.providers.jamf'));

        $this->assertInstanceOf(MdmProvider::class, $provider);
        $this->assertSame('jamf', $provider->name());
    }

    public function test_missing_file_stops_with_a_clear_reason(): void
    {
        $this->assertUnreadable(new JamfProvider('/nowhere/api-mock-response.json'), 'Jamf file not found or not readable.');
    }

    public function test_broken_json_stops_with_a_clear_reason(): void
    {
        $this->assertUnreadable($this->providerFor('{"totalCount": 7, "results": [{"id": "101"'), 'Jamf file is not valid JSON.');
    }

    public function test_missing_results_list_stops_with_a_clear_reason(): void
    {
        $this->assertUnreadable($this->providerFor('{"totalCount": 0}'), 'Jamf file has no "results" list.');
        $this->assertUnreadable($this->providerFor('{"results": "none"}'), 'Jamf file has no "results" list.');
    }

    public function test_file_that_is_not_an_object_stops_with_a_clear_reason(): void
    {
        $this->assertUnreadable($this->providerFor('[1, 2, 3]'), 'Jamf file does not contain a JSON object.');
    }

    public function test_count_mismatch_means_an_incomplete_list_and_stops(): void
    {
        $this->assertUnreadable(
            $this->providerFor('{"totalCount": 7, "results": [{"id": "101"}, {"id": "102"}]}'),
            'Jamf list is incomplete: expected 7 devices, got 2.',
        );
    }

    public function test_invalid_total_count_stops(): void
    {
        $this->assertUnreadable($this->providerFor('{"totalCount": "seven", "results": []}'), 'Jamf file has an invalid "totalCount".');
    }

    public function test_f4_a_total_count_written_as_text_is_read_as_a_number(): void
    {
        $this->assertCount(2, $this->providerFor('{"totalCount": "2", "results": [{"id": "101"}, {"id": "102"}]}')->fetch());
        $this->assertUnreadable(
            $this->providerFor('{"totalCount": "7", "results": [{"id": "101"}, {"id": "102"}]}'),
            'Jamf list is incomplete: expected 7 devices, got 2.',
        );
    }

    public function test_f9_the_invisible_marker_some_windows_tools_add_is_ignored(): void
    {
        $mock = file_get_contents(config('mdm.jamf.mock_path'));

        $this->assertSame(
            (new JamfProvider(config('mdm.jamf.mock_path')))->fetch(),
            $this->providerFor("\xEF\xBB\xBF".$mock)->fetch(),
        );
    }

    public function test_missing_total_count_is_accepted(): void
    {
        $results = $this->providerFor('{"results": [{"id": "101"}, {"id": "102"}]}')->fetch();

        $this->assertCount(2, $results);
    }

    public function test_empty_list_is_accepted(): void
    {
        $this->assertSame([], $this->providerFor('{"totalCount": 0, "results": []}')->fetch());
    }

    private function providerFor(string $contents): JamfProvider
    {
        $path = tempnam(sys_get_temp_dir(), 'jamf');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return new JamfProvider($path);
    }

    private function assertUnreadable(JamfProvider $provider, string $reason): void
    {
        try {
            $provider->fetch();
            $this->fail('Expected the Jamf list to be rejected.');
        } catch (UnreadableSourceException $e) {
            $this->assertSame($reason, $e->getMessage());
        }
    }
}
