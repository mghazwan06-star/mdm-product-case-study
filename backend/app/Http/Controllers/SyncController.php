<?php

namespace App\Http\Controllers;

use App\Mdm\MdmSyncService;
use App\Mdm\ProviderRegistry;
use Illuminate\Http\JsonResponse;

/**
 * The Sync Now button.
 */
class SyncController extends Controller
{
    public function __invoke(MdmSyncService $service, ProviderRegistry $providers): JsonResponse
    {
        $result = $service->sync($providers->get());

        return response()->json($result->toArray(), $result->succeeded() ? 200 : 503);
    }
}
