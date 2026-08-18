<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Monetisasi (dark launch)
    |--------------------------------------------------------------------------
    | Saklar utama disimpan di app_branding.monetization_launched (default false).
    | Selama belum diluncurkan, sekolah tidak melihat billing/add-on sama sekali.
    | ENV ini hanya cadangan jika baris branding belum ada.
    */
    'launched' => filter_var(env('MONETIZATION_LAUNCHED', false), FILTER_VALIDATE_BOOLEAN),

    /** Kuota penyimpanan default per institusi (MB) jika belum di-override. */
    'default_storage_quota_mb' => (int) env('MONETIZATION_DEFAULT_STORAGE_MB', 5120),

    /** Key add-on bawaan. */
    'addon_keys' => [
        'storage_upgrade' => 'storage_upgrade',
        'online_exam' => 'online_exam',
    ],
];
