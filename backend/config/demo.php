<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sekolah demo publik (SMA 1 Demo Servrin)
    |--------------------------------------------------------------------------
    | Digunakan seeder, reset harian, dan dokumentasi kredensial.
    */
    'npsn' => env('DEMO_SCHOOL_NPSN', '99990001'),
    'name' => env('DEMO_SCHOOL_NAME', 'SMA 1 Demo Servrin'),
    'password' => env('DEMO_SCHOOL_PASSWORD', 'DemoServrin1!'),
    'admin_email' => env('DEMO_SCHOOL_ADMIN_EMAIL', 'admin@demo.servrin.id'),
    'teacher_email' => env('DEMO_SCHOOL_TEACHER_EMAIL', 'guru01@demo.servrin.id'),
    'bk_email' => env('DEMO_SCHOOL_BK_EMAIL', 'bk@demo.servrin.id'),
    'student_nik' => env('DEMO_SCHOOL_STUDENT_NIK', '3201990000000001'),
    'student_birth_date' => env('DEMO_SCHOOL_STUDENT_BIRTH', '2008-05-15'),
    'parent_email' => env('DEMO_SCHOOL_PARENT_EMAIL', 'ortu@demo.servrin.id'),
    'website' => env('DEMO_SCHOOL_WEBSITE', env('FRONTEND_URL', 'https://servr.in')),
    'reset_enabled' => filter_var(env('DEMO_SCHOOL_RESET_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
