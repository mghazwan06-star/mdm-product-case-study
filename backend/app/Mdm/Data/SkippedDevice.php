<?php

namespace App\Mdm\Data;

/**
 * An entry that was skipped, with the reason.
 */
final readonly class SkippedDevice
{
    public function __construct(
        public SkipReason $reason,
        public ?string $serial = null,
        public ?string $externalId = null,
    ) {}

    /**
     * A skipped entry with a readable serial still counts as "seen", so the
     * laptop isn't flagged as missing from the MDM.
     */
    public function countsAsSeen(): bool
    {
        return $this->serial !== null;
    }
}
