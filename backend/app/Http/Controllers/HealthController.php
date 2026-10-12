<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Reports whether the backend can reach its database and the Jamf mock file.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->checkDatabase();
        $jamfFile = $this->checkJamfFile();

        $ok = $database['ok'] && $jamfFile['ok'];

        return response()->json([
            'status' => $ok ? 'ok' : 'error',
            'backend' => ['ok' => true, 'message' => 'Running'],
            'database' => $database,
            'jamf_file' => $jamfFile,
        ], $ok ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            $version = DB::selectOne('select version() as version')->version;

            return ['ok' => true, 'message' => "Connected (MySQL {$version})"];
        } catch (Throwable) {
            return ['ok' => false, 'message' => 'Cannot reach the database'];
        }
    }

    private function checkJamfFile(): array
    {
        $path = config('mdm.jamf.mock_path');
        $ok = is_string($path) && is_file($path) && is_readable($path);

        return ['ok' => $ok, 'message' => $ok ? 'Found and readable' : 'Not found or not readable'];
    }
}
