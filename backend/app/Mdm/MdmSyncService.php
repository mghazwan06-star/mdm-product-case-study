<?php

namespace App\Mdm;

use App\Enums\AssetStatus;
use App\Mdm\Contracts\MdmProvider;
use App\Mdm\Data\DeviceData;
use App\Mdm\Data\OwnerData;
use App\Mdm\Data\SkippedDevice;
use App\Mdm\Data\SkipReason;
use App\Mdm\Exceptions\UnreadableSourceException;
use App\Models\Asset;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Makes WorkWize's copy match an MDM provider's list. Provider-agnostic:
 * all database logic lives here, providers only read and translate.
 *
 * Steps: fetch → translate (+ duplicates) → apply in one transaction → result.
 */
final class MdmSyncService
{
    private const UNREADABLE_MESSAGE = "Couldn't read the %s list. Nothing was changed. Try again later.";

    private const SAVE_FAILED_MESSAGE = 'Something went wrong while saving. Nothing was changed. Try again later.';

    // The fields below hold the current run only, so the steps can share counts and
    // skips without passing them around; start() resets them before every sync.
    private MdmProvider $provider;

    /** @var array<string, int> */
    private array $counts;

    /** @var list<array{serial: ?string, external_id: ?string, reason: string}> */
    private array $skipped;

    /** @var array<string, true> every readable serial in the list, including skipped entries */
    private array $seenSerials;

    /** @var array<string, Employee> people already handled in this sync, by email */
    private array $employees;

    private Carbon $now;

    public function sync(MdmProvider $provider): SyncResult
    {
        $this->start($provider);

        // 1. Read the whole list. If it can't be trusted, nothing is touched.
        try {
            $entries = $provider->fetch();
        } catch (UnreadableSourceException $e) {
            return SyncResult::failed($provider->name(), sprintf(self::UNREADABLE_MESSAGE, $provider->label()), $e->getMessage());
        }

        // 2. Translate every entry; broken ones and conflicting duplicates are skipped with a reason.
        $devices = $this->translate($entries);

        // 3. Apply everything in one transaction: all of it or none of it.
        try {
            DB::transaction(function () use ($devices) {
                foreach ($devices as $device) {
                    $this->apply($device);
                }
            });
        } catch (Throwable $e) {
            report($e);

            return SyncResult::failed($provider->name(), self::SAVE_FAILED_MESSAGE, $e->getMessage());
        }

        $this->counts['skipped'] = count($this->skipped);

        return new SyncResult(SyncResult::STATUS_SUCCESS, $provider->name(), null, null, $this->counts, $this->skipped, array_map(strval(...), array_keys($this->seenSerials)));
    }

    private function start(MdmProvider $provider): void
    {
        $this->provider = $provider;
        $this->skipped = [];
        $this->seenSerials = [];
        $this->employees = [];
        $this->now = Carbon::now();
        $this->counts = [
            'assets_created' => 0,
            'assets_updated' => 0,
            'assets_unchanged' => 0,
            'employees_created' => 0,
            'employees_updated' => 0,
            'employees_unchanged' => 0,
            'became_to_be_collected' => 0,
            'skipped' => 0,
        ];
    }

    /**
     * @param  list<mixed>  $entries
     * @return list<DeviceData>
     */
    private function translate(array $entries): array
    {
        $mapper = $this->provider->mapper();

        /** @var array<string, list<DeviceData|SkippedDevice>> $bySerial entries grouped by serial, in list order */
        $bySerial = [];

        foreach ($entries as $entry) {
            $device = $mapper->map($entry);

            if ($device->serial === null) {
                $this->skip($device);
            } else {
                $bySerial[$device->serial][] = $device;
            }
        }

        $devices = [];

        foreach ($bySerial as $serial => $group) {
            $this->seenSerials[$serial] = true;
            $first = $group[0];

            if (count($group) > 1 && ! $this->allIdentical($group)) {
                // Jamf lists the same laptop twice with conflicting information: Jamf is
                // contradicting itself, so nothing changes for that laptop and every entry is reported.
                foreach ($group as $device) {
                    $this->skip(new SkippedDevice(SkipReason::ListedTwice, $serial, $device->externalId));
                }
            } elseif ($first instanceof SkippedDevice) {
                $this->skip($first);
            } else {
                // A single entry, or identical duplicates (harmless): used normally.
                $devices[] = $first;
            }
        }

        return $devices;
    }

    /**
     * @param  list<DeviceData|SkippedDevice>  $group
     */
    private function allIdentical(array $group): bool
    {
        foreach ($group as $device) {
            if (! $device instanceof DeviceData || $device != $group[0]) {
                return false;
            }
        }

        return true;
    }

    private function apply(DeviceData $device): void
    {
        if ($device->owner !== null) {
            $this->applyAssigned($device, $this->employeeFor($device->owner));

            return;
        }

        $asset = Asset::query()->where('serial_code', $device->serial)->first();

        // New laptop without an owner: never imported (brief rule 3).
        if ($asset === null) {
            $this->skip(new SkippedDevice(SkipReason::NoOwner, $device->serial, $device->externalId));

            return;
        }

        // Known laptop that lost its owner in the MDM: to be collected from the last owner.
        if ($asset->status !== AssetStatus::ToBeCollected) {
            $this->counts['became_to_be_collected']++;
        }

        $asset->status = AssetStatus::ToBeCollected;
        $asset->employee_id = null;
        $this->fillDetails($asset, $device);
        $this->saveAsset($asset);
    }

    private function applyAssigned(DeviceData $device, Employee $employee): void
    {
        $asset = Asset::query()->firstOrNew(['serial_code' => $device->serial]);

        $asset->status = AssetStatus::Assigned;
        $asset->employee_id = $employee->id;
        $asset->last_owner_name = $employee->name;
        $asset->last_owner_email = $employee->email;
        $this->fillDetails($asset, $device);
        $this->saveAsset($asset);
    }

    /**
     * Find or create the person by email. Within one sync, a person's details
     * come from their first entry in the list; later entries don't overwrite them.
     */
    private function employeeFor(OwnerData $owner): Employee
    {
        if (isset($this->employees[$owner->email])) {
            return $this->employees[$owner->email];
        }

        $employee = Employee::query()->firstOrNew(['email' => $owner->email]);
        $employee->email = $owner->email;
        $employee->name = $owner->name;
        $employee->job_title = $owner->jobTitle;
        $employee->phone = $owner->phone;
        $employee->username = $owner->username;

        if (! $employee->exists) {
            $this->counts['employees_created']++;
        } elseif ($employee->isDirty()) {
            $this->counts['employees_updated']++;
        } else {
            $this->counts['employees_unchanged']++;
        }

        $employee->save();

        return $this->employees[$owner->email] = $employee;
    }

    /**
     * Mirror every detail from the MDM, blanks included.
     */
    private function fillDetails(Asset $asset, DeviceData $device): void
    {
        $asset->serial_code = $device->serial;
        $asset->provider = $this->provider->name();
        $asset->external_id = $device->externalId;
        $asset->udid = $device->udid;
        $asset->device_name = $device->deviceName;
        $asset->last_contact_at = $device->lastContactAt;
        $asset->last_report_at = $device->lastReportAt;

        // MySQL stores JSON keys in its own order, so compare the specs ignoring order.
        if (! $this->sameAttributes($asset->attributes, $device->attributes)) {
            $asset->attributes = $device->attributes;
        }
    }

    /**
     * Count the change, then record when we saw it. Our own timestamps never
     * make a laptop count as updated, and an unchanged laptop keeps its updated_at.
     */
    private function saveAsset(Asset $asset): void
    {
        if (! $asset->exists) {
            $this->counts['assets_created']++;
        } elseif ($asset->isDirty()) {
            $this->counts['assets_updated']++;
        } else {
            $this->counts['assets_unchanged']++;
            $asset->timestamps = false;
        }

        $asset->last_seen_in_mdm_at = $this->now;
        $asset->last_synced_at = $this->now;
        $asset->save();
    }

    private function sameAttributes(?array $stored, array $incoming): bool
    {
        if ($stored === null) {
            return false;
        }

        ksort($stored);
        ksort($incoming);

        return $stored === $incoming;
    }

    private function skip(SkippedDevice $device): void
    {
        $this->skipped[] = [
            'serial' => $device->serial,
            'external_id' => $device->externalId,
            'reason' => $device->reason->label($this->provider->label()),
        ];
    }
}
