<?php

use Illuminate\Support\Facades\Route;

// Root API route - redirect to latest version
Route::get('/', function () {
    return response()->json([
        'message' => 'servr.in API',
        'current_version' => 'v1',
        'versions' => [
            'v1' => '/api/v1',
        ],
        'note' => 'Please use versioned endpoints. Example: /api/v1/login',
    ]);
});

// Legacy routes - redirect to v1 for backward compatibility
Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1.php';
});
