<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Http\Resources\AssetDetailResource;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssetController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return AssetResource::collection(Asset::with('employee')->orderBy('device_name')->get());
    }

    public function show(Asset $asset): AssetDetailResource
    {
        return new AssetDetailResource($asset->load('employee'));
    }

    /**
     * Deletes from WorkWize only. The laptop comes back on the next sync if the
     * MDM still assigns it (Assigned, or Owner removed in WorkWize).
     */
    public function destroy(Asset $asset): JsonResponse
    {
        $returnsOnNextSync = in_array($asset->status, [AssetStatus::Assigned, AssetStatus::OwnerRemovedLocally], true);

        $asset->delete();

        return response()->json([
            'message' => "{$asset->device_name} was deleted from WorkWize.",
            'returns_on_next_sync' => $returnsOnNextSync,
        ]);
    }
}
