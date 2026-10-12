<?php

namespace App\Mdm\Data;

use Carbon\CarbonImmutable;

/**
 * One device from any MDM provider, in a provider-neutral shape.
 */
final readonly class DeviceData
{
    /**
     * @param  OwnerData|null  $owner  null when the provider lists no owner
     * @param  array<string, mixed>  $attributes  hardware specs and other details, blanks as null
     */
    public function __construct(
        public string $serial,
        public ?OwnerData $owner,
        public string $deviceName,
        public ?string $externalId,
        public ?string $udid,
        public array $attributes,
        public ?CarbonImmutable $lastContactAt,
        public ?CarbonImmutable $lastReportAt,
    ) {}
}
