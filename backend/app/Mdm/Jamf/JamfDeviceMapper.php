<?php

namespace App\Mdm\Jamf;

use App\Mdm\Contracts\DeviceMapper;
use App\Mdm\Data\DeviceData;
use App\Mdm\Data\OwnerData;
use App\Mdm\Data\SkippedDevice;
use App\Mdm\Data\SkipReason;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Throwable;

/**
 * Translates one Jamf computer entry into DeviceData. The only place that
 * knows Jamf's field names (see docs/DECISIONS.md for the rules).
 */
final class JamfDeviceMapper implements DeviceMapper
{
    private const SECTIONS = ['general', 'purchasing', 'userAndLocation', 'hardware'];

    private const MAX_SERIAL_LENGTH = 255;

    public function map(mixed $entry): DeviceData|SkippedDevice
    {
        if (! is_array($entry) || array_is_list($entry)) {
            return new SkippedDevice(SkipReason::IncompleteInformation);
        }

        $serial = $this->serial($entry);
        $externalId = $this->text($entry['id'] ?? null);

        foreach (self::SECTIONS as $section) {
            if (isset($entry[$section]) && ! is_array($entry[$section])) {
                return new SkippedDevice(SkipReason::IncompleteInformation, $serial, $externalId);
            }
        }

        if ($serial === null) {
            return new SkippedDevice(SkipReason::NoSerial, null, $externalId);
        }

        // Serials identify laptops, so they're never cut short; an over-long one skips
        // only this entry (P2; finding F1). Descriptive text is stored in full.
        if (mb_strlen($serial) > self::MAX_SERIAL_LENGTH) {
            return new SkippedDevice(SkipReason::SerialTooLong, null, $externalId);
        }

        $email = data_get($entry, 'userAndLocation.email');
        $owner = null;

        // A blank email means "no owner". A present but invalid one is a broken entry,
        // so a typo in Jamf can't mark a laptop as "to be collected".
        if (! ($email === null || (is_string($email) && trim($email) === ''))) {
            $email = is_string($email) ? strtolower(trim($email)) : null;

            if ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                return new SkippedDevice(SkipReason::InvalidEmail, $serial, $externalId);
            }

            $owner = new OwnerData(
                email: $email,
                name: $this->text(data_get($entry, 'userAndLocation.realname')) ?? $email,
                jobTitle: $this->text(data_get($entry, 'userAndLocation.position')),
                phone: $this->text(data_get($entry, 'userAndLocation.phone')),
                username: $this->text(data_get($entry, 'userAndLocation.username')),
            );
        }

        $model = $this->text(data_get($entry, 'hardware.model'));

        return new DeviceData(
            serial: $serial,
            owner: $owner,
            deviceName: $this->text(data_get($entry, 'general.name')) ?? $model ?? "Unknown device ({$serial})",
            externalId: $externalId,
            udid: $this->text($entry['udid'] ?? null),
            attributes: [
                'make' => $this->text(data_get($entry, 'hardware.make')),
                'model' => $model,
                'model_identifier' => $this->text(data_get($entry, 'hardware.modelIdentifier')),
                'processor' => $this->text(data_get($entry, 'hardware.processorType')),
                'cores' => $this->positiveInt(data_get($entry, 'hardware.coreCount')),
                'ram_mb' => $this->positiveInt(data_get($entry, 'hardware.totalRamMegabytes')),
                'battery_percent' => $this->percent(data_get($entry, 'hardware.batteryCapacityPercent')),
                'platform' => $this->text(data_get($entry, 'general.platform')),
                'site' => $this->text(data_get($entry, 'general.site.name')),
                'purchased' => is_bool($purchased = data_get($entry, 'purchasing.purchased')) ? $purchased : null,
                'purchase_date' => $this->date(data_get($entry, 'purchasing.poDate')),
            ],
            lastContactAt: $this->timestamp(data_get($entry, 'general.lastContactTime')),
            lastReportAt: $this->timestamp(data_get($entry, 'general.reportDate')),
        );
    }

    private function serial(array $entry): ?string
    {
        $hardware = $entry['hardware'] ?? null;
        $serial = is_array($hardware) ? $this->text($hardware['serialNumber'] ?? null) : null;

        // Tidied so the same laptop always matches: capitals, and no spaces anywhere
        // ("C02 XK1" is "C02XK1"). Apple serials never contain spaces (P4; finding F5).
        return $serial === null ? null : strtoupper(preg_replace('/\s+/u', '', $serial));
    }

    /**
     * Trimmed text, or null when blank (blank in Jamf means blank here).
     */
    private function text(mixed $value): ?string
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function positiveInt(mixed $value): ?int
    {
        return is_int($value) && $value > 0 ? $value : null;
    }

    private function percent(mixed $value): ?int
    {
        return is_int($value) && $value >= 0 && $value <= 100 ? $value : null;
    }

    /**
     * A calendar date such as "2022-06-15", or null.
     */
    private function date(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        return $date !== false && $date->format('Y-m-d') === trim($value) ? $date->format('Y-m-d') : null;
    }

    private function timestamp(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->utc();
        } catch (Throwable) {
            return null;
        }
    }
}
