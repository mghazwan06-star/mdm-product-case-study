<?php

namespace App\Http;

use Illuminate\Database\LostConnectionDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Turns unexpected errors in the API into plain messages. Technical details
 * (database address, SQL, traces) never reach the website; they stay in the
 * server log (P11; findings F2 and F12).
 */
final class PlainErrors
{
    public const DATABASE_UNREACHABLE = "WorkWize's database isn't reachable right now. Try again in a minute.";

    public const DATABASE_UNREACHABLE_SYNC = "WorkWize's database isn't reachable right now. Nothing was changed. Try again in a minute.";

    public const SOMETHING_WENT_WRONG = 'Something went wrong in WorkWize. Try again in a minute.';

    public const SOMETHING_WENT_WRONG_SYNC = 'Something went wrong during the sync. Nothing was changed. Try again in a minute.';

    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        $databaseDown = (new LostConnectionDetector)->causedByLostConnection($e);
        $isSync = $request->is('api/sync');

        $message = match (true) {
            $databaseDown && $isSync => self::DATABASE_UNREACHABLE_SYNC,
            $databaseDown => self::DATABASE_UNREACHABLE,
            $isSync => self::SOMETHING_WENT_WRONG_SYNC,
            default => self::SOMETHING_WENT_WRONG,
        };

        // A failed sync keeps the sync result's shape, so the website shows it like any other failed sync.
        $body = $isSync ? ['status' => 'failed', 'message' => $message] : ['message' => $message];

        return response()->json($body, $databaseDown ? 503 : 500);
    }
}
