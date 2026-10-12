<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A laptop in the laptops list.
 *
 * @mixin Asset
 */
class AssetResource extends JsonResource
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
            ],
            'last_owner_name' => $this->last_owner_name,
        ];
    }
}
