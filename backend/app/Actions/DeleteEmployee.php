<?php

namespace App\Actions;

use App\Enums\AssetStatus;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a person from WorkWize only (docs/DECISIONS.md row 15). Their current laptops stay, marked
 * "Owner removed in WorkWize", and return to them on the next sync if Jamf still
 * assigns them. Never marks anything "To be collected": nothing changed in Jamf (P9).
 */
final class DeleteEmployee
{
    /**
     * @return int the number of laptops that were linked to the person
     */
    public function __invoke(Employee $employee): int
    {
        return DB::transaction(function () use ($employee) {
            $affected = $employee->assets()->update(['status' => AssetStatus::OwnerRemovedLocally->value]);

            // The database clears the laptops' owner link; the last-owner snapshot stays.
            $employee->delete();

            return $affected;
        });
    }
}
