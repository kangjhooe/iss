<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Frontend application URL
    |--------------------------------------------------------------------------
    | Base URL untuk aplikasi frontend (Vue). Digunakan untuk link verifikasi
    | email, reset password, dan redirect setelah login.
    */
    'url' => env('FRONTEND_URL', 'http://localhost:5173'),

    /*
    | Domain cookie auth/impersonation. Null = host saat ini (localhost / api).
    | Harus selaras dengan SESSION_DOMAIN di production (mis. .servr.in).
    */
    'cookie_domain' => ($domain = env('COOKIE_DOMAIN')) !== null && $domain !== '' ? $domain : null,

    'skip_email_verification' => filter_var(env('SKIP_EMAIL_VERIFICATION', false), FILTER_VALIDATE_BOOLEAN),

];
