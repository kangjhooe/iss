<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => config('app.name') . ' API',
        'version' => '1.0.0',
    ]);
});
