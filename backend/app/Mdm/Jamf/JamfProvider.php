<?php

namespace App\Mdm\Jamf;

use App\Mdm\Contracts\DeviceMapper;
use App\Mdm\Contracts\MdmProvider;
use App\Mdm\Exceptions\UnreadableSourceException;
use JsonException;

/**
 * Reads devices from the Jamf API mock response file.
 */
final class JamfProvider implements MdmProvider
{
    private readonly string $path;

    public function __construct(?string $path = null)
    {
        $this->path = $path ?? (string) config('mdm.jamf.mock_path');
    }

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
        $payload = $this->readPayload();

        $results = $payload['results'] ?? null;

        if (! is_array($results) || ! array_is_list($results)) {
            throw new UnreadableSourceException('Jamf file has no "results" list.');
        }

        // Jamf's API is paginated: totalCount is the number of devices across ALL pages,
        // while results holds one page. Once every page has been collected, the number of
        // devices must equal totalCount; otherwise the list is incomplete and is rejected
        // as a whole (all or nothing). The mock file is a single page, so its results are
        // the whole list. If totalCount is missing we can't check, so we don't fail.
        if (array_key_exists('totalCount', $payload)) {
            $totalCount = $payload['totalCount'];

            if (! is_int($totalCount) || $totalCount < 0) {
                throw new UnreadableSourceException('Jamf file has an invalid "totalCount".');
            }

            if (count($results) !== $totalCount) {
                throw new UnreadableSourceException(sprintf(
                    'Jamf list is incomplete: expected %d devices, got %d.',
                    $totalCount,
                    count($results),
                ));
            }
        }

        return $results;
    }

    public function mapper(): DeviceMapper
    {
        return new JamfDeviceMapper;
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(): array
    {
        if (! is_file($this->path) || ! is_readable($this->path)) {
            throw new UnreadableSourceException('Jamf file not found or not readable.');
        }

        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new UnreadableSourceException('Jamf file could not be read.');
        }

        try {
            $payload = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new UnreadableSourceException('Jamf file is not valid JSON.');
        }

        if (! is_array($payload) || array_is_list($payload)) {
            throw new UnreadableSourceException('Jamf file does not contain a JSON object.');
        }

        return $payload;
    }
}
