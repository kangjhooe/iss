<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => (function () {
        $allowed = env('CORS_ALLOWED_ORIGINS')
            ? explode(',', env('CORS_ALLOWED_ORIGINS'))
            : ['http://localhost:5173', 'http://127.0.0.1:5173'];

        // Include common Vite fallback port when 5173 is occupied.
        $allowed = array_merge($allowed, ['http://localhost:5174', 'http://127.0.0.1:5174']);

        return array_values(array_unique(array_map('trim', array_filter($allowed))));
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
