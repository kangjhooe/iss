<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API wilayah Indonesia (upstream)
    |--------------------------------------------------------------------------
    | Default: wilayah.id (JSON statis, tanpa API key, data Kepmendagri).
    | Laravel memanggil sumber ini lalu di-cache; frontend tidak memanggil langsung.
    */
    'base_url' => rtrim((string) env('REGIONS_BASE_URL', 'https://wilayah.id/api'), '/'),

    /*
    | Cache TTL dalam detik (default 30 hari). Set 0 untuk non-cache.
    */
    'cache_ttl' => (int) env('REGIONS_CACHE_TTL', 2592000),

    /*
    | Timeout request ke upstream (detik).
    */
    'timeout' => (int) env('REGIONS_TIMEOUT', 10),
];
