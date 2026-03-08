<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => (function () {
        $allowed = [];

        // First, get from CORS_ALLOWED_ORIGINS env variable (comma-separated)
        if (env('CORS_ALLOWED_ORIGINS')) {
            $allowed = array_merge($allowed, explode(',', env('CORS_ALLOWED_ORIGINS')));
        }

        // Add FRONTEND_URL from config (reads env)
        if (config('frontend.url')) {
            $frontendUrl = rtrim(config('frontend.url'), '/');
            if (!in_array($frontendUrl, $allowed)) {
                $allowed[] = $frontendUrl;
            }
        }

        // Default to localhost for development if no origins are set
        if (empty($allowed)) {
            $allowed = ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://localhost:5174', 'http://127.0.0.1:5174'];
        }

        return array_values(array_unique(array_map('trim', array_filter($allowed))));
    })(),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
