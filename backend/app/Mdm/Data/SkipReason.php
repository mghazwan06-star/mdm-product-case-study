<?php

namespace App\Mdm\Data;

/**
 * Why a single entry was not imported.
 */
enum SkipReason: string
{
    case NoSerial = 'no_serial';
    case InvalidEmail = 'invalid_email';
    case IncompleteInformation = 'incomplete_information';
    case ListedTwice = 'listed_twice';
    case NoOwner = 'no_owner';

    /**
     * The wording shown to users, e.g. "no owner in Jamf".
     */
    public function label(string $providerLabel): string
    {
        return match ($this) {
            self::NoSerial => 'no serial number',
            self::InvalidEmail => 'invalid email',
            self::IncompleteInformation => 'incomplete information',
            self::ListedTwice => 'listed twice with conflicting information',
            self::NoOwner => "no owner in {$providerLabel}",
        };
    }
}
