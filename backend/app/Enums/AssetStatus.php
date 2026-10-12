<?php

namespace App\Enums;

/**
 * Where a laptop stands, based on the latest Jamf list (see docs/DECISIONS.md).
 */
enum AssetStatus: string
{
    case Assigned = 'assigned';
    case ToBeCollected = 'to_be_collected';
    case MissingFromMdm = 'missing_from_mdm';
    case OwnerRemovedLocally = 'owner_removed_locally';
}
