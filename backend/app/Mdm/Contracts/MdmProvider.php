<?php

namespace App\Mdm\Contracts;

use App\Mdm\Exceptions\UnreadableSourceException;

/**
 * An MDM tool we import laptops from (Jamf now; Intune, Kandji later).
 * Each provider only reads its source and translates entries. All database
 * logic lives in the shared sync engine.
 */
interface MdmProvider
{
    /**
     * Short identifier stored on assets and sync runs, e.g. "jamf".
     */
    public function name(): string;

    /**
     * Display name used in messages, e.g. "Jamf".
     */
    public function label(): string;

    /**
     * Every raw device entry, read and checked as a whole.
     *
     * @return list<mixed>
     *
     * @throws UnreadableSourceException when the list can't be read completely
     */
    public function fetch(): array;

    /**
     * The translator for this provider's raw entries.
     */
    public function mapper(): DeviceMapper;
}
