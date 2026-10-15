<?php

namespace App\Mdm;

use App\Enums\AssetStatus;
use App\Enums\SyncRunStatus;
use App\Mdm\Contracts\MdmProvider;
use App\Mdm\Data\DeviceData;
use App\Mdm\Data\OwnerData;
use App\Mdm\Data\SkippedDevice;
use App\Mdm\Data\SkipReason;
use App\Mdm\Exceptions\UnreadableSourceException;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\SyncRun;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Makes WorkWize's copy match an MDM provider's list. Provider-agnostic:
 * all database logic lives here, providers only read and translate.
 *
 * Steps: record the run → fetch → translate (+ duplicates) → apply in one
 * transaction → record the result. Every attempt is kept in sync_runs.
 */
final class MdmSyncService
{
    private const UNREADABLE_MESSAGE = "Couldn't read the %s list. Nothing was changed. Try again later.";

    private const SAVE_FAILED_MESSAGE = 'Something went wrong while saving. Nothing was changed. Try again later.';

    private const ALREADY_RUNNING_MESSAGE = 'A sync is already running. Please wait for it to finish.';

    private const INTERRUPTED_MESSAGE = 'The last sync was interrupted. Nothing was changed. You can sync again in about %s.';

    private const INTERRUPTED_HISTORY = 'Interrupted: the sync stopped before it finished (for example, WorkWize or its database restarted). Nothing was changed.';

    /**
     * A normal sync takes seconds (12,000 laptops: about 8). One still holding the lock
     * after this long has stopped without finishing (finding F10).
     */
    private const REALLY_RUNNING_SECONDS = 60;

    /**
     * Only one sync at a time, across every web request and the command line.
     * The lock lives in the cache (the database by default) and expires on its own
     * after this many seconds if a run ever dies without releasing it.
     */
    public const LOCK_NAME = 'mdm-sync';

    private const LOCK_SECONDS = 300;

    // The fields below hold the current run only, so the steps can share counts and
    // skips without passing them around; start() resets them before every sync.
    private MdmProvider $provider;

    /** @var array<string, int> */
    private array $counts;

    /** @var list<array{serial: ?string, external_id: ?string, reason: string}> */
    private array $skipped;

    /** @var array<string, true> every readable serial in the list, including skipped entries */
    private array $seenSerials;

    /** @var list<array{asset_id: int, status: string, device_name: string, person_name: ?string}> */
    private array $needsAttention;

    /** @var array<string, Employee> people already handled in this sync, by email */
    private array $employees;

    private Carbon $now;

    private bool $isFirstSync;

    public function sync(MdmProvider $provider): SyncResult
    {
        $lock = Cache::lock(self::LOCK_NAME, self::LOCK_SECONDS);

        if (! $lock->get()) {
            return $this->refuse($provider);
        }

        try {
            // Holding the lock means no other sync is running, so any sync still marked
            // "running" stopped without finishing. Say so instead of "running" forever (P4; F10).
            $this->closeInterruptedRuns();

            return $this->run($provider);
        } finally {
            try {
                $lock->release();
            } catch (Throwable $e) {
                report($e); // e.g. the database went away; the lock then expires by itself
            }
        }
    }

    private function closeInterruptedRuns(): void
    {
        SyncRun::where('status', SyncRunStatus::Running)->update([
            'status' => SyncRunStatus::Interrupted,
            'message' => self::INTERRUPTED_HISTORY,
        ]);
    }

    private function run(MdmProvider $provider): SyncResult
    {
        $this->start($provider);
        $run = $this->openRun();

        // 1. Read the whole list. If it can't be trusted, nothing is touched.
        try {
            $entries = $provider->fetch();
        } catch (UnreadableSourceException $e) {
            return $this->fail($run, sprintf(self::UNREADABLE_MESSAGE, $provider->label()), $e->getMessage());
        }

        // 2. Translate every entry; broken ones and conflicting duplicates are skipped with a reason.
        $devices = $this->translate($entries);

        // 3. Apply everything in one transaction: all of it or none of it.
        try {
            DB::transaction(function () use ($devices) {
                foreach ($devices as $device) {
                    $this->apply($device);
                }

                $this->flagMissing();
            });
        } catch (Throwable $e) {
            report($e);

            // The database's own error is kept in the history for developers, never sent to the website (P11; F2).
            return $this->fail($run, self::SAVE_FAILED_MESSAGE, $e->getMessage(), showError: false);
        }

        $this->counts['skipped'] = count($this->skipped);

        $run->update([
            'status' => SyncRunStatus::Success,
            'finished_at' => Carbon::now(),
            'is_first_sync' => $this->isFirstSync,
            'counts' => $this->counts,
            'skipped' => $this->skipped,
            'needs_attention' => $this->needsAttention,
        ]);

        return new SyncResult(
            SyncResult::STATUS_SUCCESS,
            $provider->name(),
            null,
            null,
            $this->counts,
            $this->skipped,
            array_map(strval(...), array_keys($this->seenSerials)),
            $this->isFirstSync,
            $run->id,
            $this->needsAttention,
            $entries === [],
        );
    }

    /**
     * Record the attempt before anything else, so failed attempts are kept too.
     * The first sync is the first one that succeeds for this provider.
     */
    private function openRun(): SyncRun
    {
        $this->isFirstSync = ! SyncRun::query()
            ->where('provider', $this->provider->name())
            ->where('status', SyncRunStatus::Success)
            ->exists();

        return SyncRun::create([
            'provider' => $this->provider->name(),
            'status' => SyncRunStatus::Running,
            'started_at' => $this->now,
        ]);
    }

    /**
     * Another sync holds the lock: record the refused attempt and change nothing.
     */
    private function refuse(MdmProvider $provider): SyncResult
    {
        $now = Carbon::now();
        $message = $this->refusalMessage($now);
        $run = SyncRun::create([
            'provider' => $provider->name(),
            'status' => SyncRunStatus::Refused,
            'started_at' => $now,
            'finished_at' => $now,
            'message' => $message,
        ]);

        return SyncResult::refused($provider->name(), $message, $run->id);
    }

    /**
     * "Already running" only while that's true. If the sync holding the lock started more
     * than a minute ago, it was interrupted: say so, and when Sync Now works again (P4, P11; F10).
     */
    private function refusalMessage(Carbon $now): string
    {
        $holder = SyncRun::whereIn('status', [SyncRunStatus::Running, SyncRunStatus::Interrupted])->latest('id')->first();

        if ($holder === null || $holder->started_at->diffInSeconds($now) < self::REALLY_RUNNING_SECONDS) {
            return self::ALREADY_RUNNING_MESSAGE;
        }

        if ($holder->status === SyncRunStatus::Running) {
            $holder->update(['status' => SyncRunStatus::Interrupted, 'message' => self::INTERRUPTED_HISTORY]);
        }

        $secondsLeft = self::LOCK_SECONDS - $holder->started_at->diffInSeconds($now);
        $minutes = max(1, (int) ceil($secondsLeft / 60));

        return sprintf(self::INTERRUPTED_MESSAGE, $minutes === 1 ? '1 minute' : "{$minutes} minutes");
    }

    private function fail(SyncRun $run, string $message, string $error, bool $showError = true): SyncResult
    {
        $run->update([
            'status' => SyncRunStatus::Failed,
            'finished_at' => Carbon::now(),
            'message' => $message,
            'error' => $error,
        ]);

        return SyncResult::failed($this->provider->name(), $message, $showError ? $error : null, $run->id);
    }

    private function start(MdmProvider $provider): void
    {
        $this->provider = $provider;
        $this->skipped = [];
        $this->seenSerials = [];
        $this->needsAttention = [];
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
            'became_missing' => 0,
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
        $becameToBeCollected = $asset->status !== AssetStatus::ToBeCollected;

        $asset->status = AssetStatus::ToBeCollected;
        $asset->employee_id = null;
        $this->fillDetails($asset, $device);
        $this->saveAsset($asset);

        if ($becameToBeCollected) {
            $this->counts['became_to_be_collected']++;
            $this->needsAttention($asset);
        }
    }

    /**
     * Laptops of this provider that the list didn't mention at all: "No longer in
     * Jamf". Kept, never deleted; the owner link is removed and the last owner
     * remembered. Entries skipped with a readable serial count as seen, so a typo
     * never flags a laptop. An empty list flags every laptop; the next good sync
     * clears the flags (a laptop that's listed again follows the list).
     */
    private function flagMissing(): void
    {
        $missing = Asset::query()
            ->where('provider', $this->provider->name())
            ->where('status', '!=', AssetStatus::MissingFromMdm)
            ->whereNotIn('serial_code', array_map(strval(...), array_keys($this->seenSerials)))
            ->get();

        foreach ($missing as $asset) {
            $asset->status = AssetStatus::MissingFromMdm;
            $asset->employee_id = null;
            $asset->save();

            $this->counts['became_missing']++;
            $this->needsAttention($asset);
        }
    }

    /**
     * A laptop that newly needs a person in this sync: listed by name in the
     * result message. Laptops still waiting from earlier syncs aren't repeated.
     */
    private function needsAttention(Asset $asset): void
    {
        $this->needsAttention[] = [
            'asset_id' => $asset->id,
            'status' => $asset->status->value,
            'device_name' => $asset->device_name,
            'person_name' => $asset->last_owner_name,
        ];
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
