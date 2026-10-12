<?php

namespace App\Models;

use App\Enums\AssetStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A laptop imported from an MDM provider. Unique by serial code.
 */
#[Fillable([
    'serial_code', 'provider', 'external_id', 'udid', 'device_name', 'status',
    'employee_id', 'last_owner_name', 'last_owner_email', 'attributes',
    'last_contact_at', 'last_report_at', 'last_seen_in_mdm_at', 'last_synced_at',
])]
class Asset extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'attributes' => 'array',
            'last_contact_at' => 'datetime',
            'last_report_at' => 'datetime',
            'last_seen_in_mdm_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * The person who has this laptop now (none for the attention statuses).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
