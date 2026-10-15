<?php

namespace App\Mdm\Jamf;

use App\Mdm\Contracts\DeviceMapper;
use App\Mdm\Data\DeviceData;
use App\Mdm\Data\OwnerData;
use App\Mdm\Data\SkippedDevice;
use App\Mdm\Data\SkipReason;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Translates one Jamf computer entry into DeviceData. The only place that
 * knows Jamf's field names (see docs/DECISIONS.md for the rules).
 */
final class JamfDeviceMapper implements DeviceMapper
{
    private const SECTIONS = ['general', 'purchasing', 'userAndLocation', 'hardware'];

    private const MAX_SERIAL_LENGTH = 255;

    // Year, month, day first; then optionally a time (with seconds and fractions) and a time zone.
    private const YEAR_FIRST = '/^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2})(?::(\d{2})(?:\.\d+)?)?)?(?:Z|[+-]\d{2}:?\d{2})?$/i';

    /** @var array<string, string> fields Jamf sent in a form WorkWize can't use, as sent */
    private array $unreadable = [];

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
            $email = is_string($email) ? mb_strtolower(trim($email)) : null;

            if ($email === null || ! $this->isValidEmail($email)) {
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

        // Every laptop field is either readable, missing ("Not provided by Jamf"), or sent in a
        // form WorkWize can't use. The last kind is kept as Jamf sent it, never guessed and never
        // hidden, so the website can say so (P4, P16; findings F3, F7, F8).
        $this->unreadable = [];
        $model = $this->read('model', data_get($entry, 'hardware.model'), $this->asText(...));

        $attributes = [
            'make' => $this->read('make', data_get($entry, 'hardware.make'), $this->asText(...)),
            'model' => $model,
            'model_identifier' => $this->read('model_identifier', data_get($entry, 'hardware.modelIdentifier'), $this->asText(...)),
            'processor' => $this->read('processor', data_get($entry, 'hardware.processorType'), $this->asText(...)),
            'cores' => $this->read('cores', data_get($entry, 'hardware.coreCount'), fn ($v) => $this->asWholeNumber($v, min: 1)),
            'ram_mb' => $this->read('ram_mb', data_get($entry, 'hardware.totalRamMegabytes'), fn ($v) => $this->asWholeNumber($v, min: 1)),
            'battery_percent' => $this->read('battery_percent', data_get($entry, 'hardware.batteryCapacityPercent'), fn ($v) => $this->asWholeNumber($v, min: 0, max: 100)),
            'platform' => $this->read('platform', data_get($entry, 'general.platform'), $this->asText(...)),
            'site' => $this->read('site', data_get($entry, 'general.site.name'), $this->asText(...)),
            'purchased' => $this->read('purchased', data_get($entry, 'purchasing.purchased'), fn ($v) => is_bool($v) ? $v : null),
            'purchase_date' => $this->read('purchase_date', data_get($entry, 'purchasing.poDate'), $this->asCalendarDate(...)),
        ];

        $device = [
            'externalId' => $this->read('external_id', $entry['id'] ?? null, $this->asText(...)),
            'udid' => $this->read('udid', $entry['udid'] ?? null, $this->asText(...)),
            'lastContactAt' => $this->read('last_contact_at', data_get($entry, 'general.lastContactTime'), $this->asTimestamp(...)),
            'lastReportAt' => $this->read('last_report_at', data_get($entry, 'general.reportDate'), $this->asTimestamp(...)),
        ];

        // Only added when there is something, so laptops without odd values keep the same shape.
        if ($this->unreadable !== []) {
            $attributes['unreadable'] = $this->unreadable;
        }

        return new DeviceData(
            serial: $serial,
            owner: $owner,
            deviceName: $this->text(data_get($entry, 'general.name')) ?? $model ?? "Unknown device ({$serial})",
            externalId: $device['externalId'],
            udid: $device['udid'],
            attributes: $attributes,
            lastContactAt: $device['lastContactAt'],
            lastReportAt: $device['lastReportAt'],
        );
    }

    /**
     * Reads one laptop field. Blank means missing (null). A value that can't be used is
     * also null, but remembered as Jamf sent it under $key.
     */
    private function read(string $key, mixed $value, callable $parse): mixed
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        $parsed = $parse($value);

        if ($parsed === null) {
            $this->unreadable[$key] = is_string($value)
                ? trim($value)
                : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        }

        return $parsed;
    }

    private function asText(mixed $value): ?string
    {
        return is_string($value) || is_int($value) || is_float($value) ? trim((string) $value) : null;
    }

    /**
     * A whole number within the possible range. Numbers sent as text ("16384") count as
     * numbers (F3); impossible ones (a battery of 140%, negative memory) don't (F8).
     */
    private function asWholeNumber(mixed $value, int $min, int $max = PHP_INT_MAX): ?int
    {
        if (is_string($value) && preg_match('/^\d{1,18}$/', trim($value))) {
            $value = (int) trim($value);
        } elseif (is_float($value) && is_finite($value) && floor($value) === $value && abs($value) < 2 ** 62) {
            $value = (int) $value;
        }

        return is_int($value) && $value >= $min && $value <= $max ? $value : null;
    }

    /**
     * A moment written year first: "2026-01-05T10:30:12Z", with or without seconds,
     * time or time zone. A missing time zone means UTC (written down as an assumption).
     * Anything else, like "05/01/2026", is never guessed (F7).
     */
    private function asTimestamp(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || preg_match(self::YEAR_FIRST, trim($value), $m) !== 1) {
            return null;
        }

        [$year, $month, $day] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        $timeIsValid = ($m[4] ?? '') === '' || ((int) $m[4] <= 23 && (int) $m[5] <= 59 && (int) ($m[6] ?? 0) <= 59);

        if (! checkdate($month, $day, $year) || ! $timeIsValid) {
            return null;
        }

        try {
            return CarbonImmutable::parse(trim($value), 'UTC')->utc();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * A calendar date such as "2022-06-15" (a year-first moment also works: its date is used).
     */
    private function asCalendarDate(mixed $value): ?string
    {
        return $this->asTimestamp($value) === null ? null : substr(trim($value), 0, 10);
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
     * A valid email address, including international ones with accents before or after
     * the @ ("josé@…", "…@exämple.com"). Real people have these addresses (finding F6).
     */
    private function isValidEmail(string $email): bool
    {
        $at = mb_strrpos($email, '@');

        if ($at === false || mb_strlen($email) > 254) {
            return false;
        }

        $local = mb_substr($email, 0, $at);
        $domain = mb_substr($email, $at + 1);

        // The part before the @ uses PHP's own check (which allows accents); the domain
        // is letters (any alphabet), digits and hyphens, with at least one dot.
        return filter_var($local.'@example.com', FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE) !== false
            && preg_match('/^(?:[\p{L}\p{N}](?:[\p{L}\p{N}-]{0,61}[\p{L}\p{N}])?\.)+\p{L}{2,63}$/u', $domain) === 1;
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
}
