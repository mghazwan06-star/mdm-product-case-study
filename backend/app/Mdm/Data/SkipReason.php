<?php

namespace App\Mdm\Data;

/**
 * Why a single entry was not imported. Values are the wording shown to users.
 */
enum SkipReason: string
{
    case NoSerial = 'no serial number';
    case InvalidEmail = 'invalid email';
    case IncompleteInformation = 'incomplete information';
    case ListedTwice = 'listed twice';
    case NoOwner = 'no owner in Jamf';
}
