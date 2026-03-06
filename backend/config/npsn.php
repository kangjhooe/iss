<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NPSN Referensi URL (Kemendikbud)
    |--------------------------------------------------------------------------
    | Base URL untuk validasi NPSN ke data referensi Kemendikbud.
    | Format: {base_url}/pendidikan/npsn/{npsn}
    */
    'referensi_base_url' => env('NPSN_REFERENSI_URL', 'https://referensi.data.kemendikdasmen.go.id'),

    /*
    | Cache TTL dalam detik (default 7 hari). Set 0 untuk non-cache.
    */
    'cache_ttl' => (int) env('NPSN_CACHE_TTL', 604800),

    /*
    | Timeout request ke referensi (detik).
    | Jika referensi Kemendikbud tidak dapat diakses, validasi NPSN akan gagal (fail-closed).
    */
    'timeout' => (int) env('NPSN_TIMEOUT', 10),
];
