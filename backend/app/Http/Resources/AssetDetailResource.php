<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything we store about one laptop, for the laptop details page.
 * Missing values are null; the website shows "Not provided by Jamf".
 *
 * @mixin Asset
 */
class AssetDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'serial_code' => $this->serial_code,
            'status' => $this->status->value,
            'owner' => $this->employee === null ? null : [
                'id' => $this->employee->id,
                'name' => $this->employee->name,
                'email' => $this->employee->email,
                'job_title' => $this->employee->job_title,
                'phone' => $this->employee->phone,
            ],
            'last_owner_name' => $this->last_owner_name,
            'last_owner_email' => $this->last_owner_email,
            'attributes' => $this->attributes ?? (object) [],
            'provider' => $this->provider,
            'external_id' => $this->external_id,
            'udid' => $this->udid,
            'last_contact_at' => $this->last_contact_at?->toIso8601String(),
            'last_report_at' => $this->last_report_at?->toIso8601String(),
            'last_seen_in_mdm_at' => $this->last_seen_in_mdm_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}
