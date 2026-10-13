<?php

namespace App\Models;

use App\Enums\SyncRunStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One sync attempt and its result.
 */
#[Fillable([
    'provider', 'status', 'started_at', 'finished_at', 'is_first_sync',
    'counts', 'needs_attention', 'skipped', 'stale_reference_at', 'message', 'error',
])]
class SyncRun extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SyncRunStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'is_first_sync' => 'boolean',
            'counts' => 'array',
            'needs_attention' => 'array',
            'skipped' => 'array',
            'stale_reference_at' => 'datetime',
        ];
    }
}
