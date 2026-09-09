<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'success',
        'service' => config('app.name', 'Summit API'),
        'environment' => config('app.env', 'production'),
        'version' => 'v1',
        'api_prefix' => '/api/v1',
        'documentation_url' => url('/api/documentation'),
        'timestamp' => now()->toIso8601String(),
    ]);
});

Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Resource atau rute web tidak ditemukan (404).',
        'error_code' => 'ERR_NOT_FOUND',
    ], 404);
});
