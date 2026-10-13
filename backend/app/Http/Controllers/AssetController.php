<?php

namespace App\Http\Controllers;

use App\Enums\AssetStatus;
use App\Http\Resources\AssetDetailResource;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AssetController extends Controller
{
    /**
     * Statuses that need a person: what the "Needs attention" button counts and filters.
     */
    private const NEEDS_ATTENTION = [
        AssetStatus::ToBeCollected,
        AssetStatus::MissingFromMdm,
        AssetStatus::OwnerRemovedLocally,
    ];

    /**
     * All laptops, or only those that need attention (?needs_attention=1).
     * meta.needs_attention_count is always the total that need attention.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $assets = Asset::with('employee')
            ->when($request->boolean('needs_attention'), fn ($query) => $query->whereIn('status', self::NEEDS_ATTENTION))
            ->orderBy('device_name')
            ->get();

        return AssetResource::collection($assets)->additional([
            'meta' => ['needs_attention_count' => Asset::whereIn('status', self::NEEDS_ATTENTION)->count()],
        ]);
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
