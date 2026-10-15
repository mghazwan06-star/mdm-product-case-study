<?php

namespace Tests\Support;

use App\Mdm\Contracts\DeviceMapper;
use App\Mdm\Contracts\MdmProvider;
use App\Mdm\Jamf\JamfDeviceMapper;
use RuntimeException;

/**
 * A provider that fails with an unexpected error full of technical details,
 * to prove those details never reach the website.
 */
final class ExplodingProvider implements MdmProvider
{
    public const SECRET = 'Secret detail: db.internal:3306 /var/www/backend';

    public function name(): string
    {
        return 'exploding';
    }

    public function label(): string
    {
        return 'Exploding MDM';
    }

    public function fetch(): array
    {
        throw new RuntimeException(self::SECRET);
    }

    public function mapper(): DeviceMapper
    {
        return new JamfDeviceMapper;
    }
}
