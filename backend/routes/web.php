<?php

use Illuminate\Support\Facades\Route;

// The backend only serves the API; the website runs separately.
Route::get('/', fn () => response()->json([
    'name' => 'WorkWize MDM Sync API',
    'website' => 'http://localhost:5173',
    'health' => url('/api/health'),
], options: JSON_UNESCAPED_SLASHES));
