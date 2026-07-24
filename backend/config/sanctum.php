<?php

use Laravel\Sanctum\Sanctum;

return [

    'stateful' => (function () {
        $stateful = explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
            '%s%s%s',
            'localhost,localhost:3000,localhost:5173,127.0.0.1,127.0.0.1:8000,::1',
            Sanctum::currentApplicationUrlWithPort(),
            env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
        )));

        // Include common Vite fallback port when 5173 is occupied.
        $stateful = array_merge($stateful, ['localhost:5174', '127.0.0.1:5174']);

        return array_values(array_unique(array_map('trim', array_filter($stateful))));
    })(),

    'guard' => ['web'],

    'expiration' => env('SANCTUM_TOKEN_EXPIRATION') !== null && env('SANCTUM_TOKEN_EXPIRATION') !== ''
        ? (int) env('SANCTUM_TOKEN_EXPIRATION')
        : null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
