<?php

namespace App\Mdm\Contracts;

use App\Mdm\Data\DeviceData;
use App\Mdm\Data\SkippedDevice;

/**
 * Translates one raw provider entry into the standard device shape.
 */
interface DeviceMapper
{
    public function map(mixed $entry): DeviceData|SkippedDevice;
}
