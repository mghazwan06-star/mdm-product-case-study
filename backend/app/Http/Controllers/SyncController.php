<?php

namespace App\Http\Controllers;

use App\Mdm\MdmSyncService;
use App\Mdm\ProviderRegistry;
use App\Mdm\SyncResult;
use Illuminate\Http\JsonResponse;

/**
 * The Sync Now button.
 */
class SyncController extends Controller
{
    public function __invoke(MdmSyncService $service, ProviderRegistry $providers): JsonResponse
    {
        $result = $service->sync($providers->get());

        $status = match ($result->status) {
            SyncResult::STATUS_SUCCESS => 200,
            SyncResult::STATUS_REFUSED => 409, // another sync is running
            default => 503,                    // the list couldn't be read or saved
        };

        return response()->json($result->toArray(), $status);
    }
}
