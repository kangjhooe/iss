<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'servr.in API',
        'version' => '1.0.0',
    ]);
});
