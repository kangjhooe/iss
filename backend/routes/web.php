<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Indonesia Smart School API',
        'version' => '1.0.0',
    ]);
});
