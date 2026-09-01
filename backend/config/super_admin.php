<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Super Admin seeder
    |--------------------------------------------------------------------------
    | Kredensial hanya dari env. Seeder tidak mereset password yang sudah ada
    | kecuali SUPER_ADMIN_RESET_PASSWORD=true.
    */
    'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
    'email' => env('SUPER_ADMIN_EMAIL', 'superadmin@iss.id'),
    'password' => env('SUPER_ADMIN_PASSWORD'),
    'reset_password' => filter_var(env('SUPER_ADMIN_RESET_PASSWORD', false), FILTER_VALIDATE_BOOLEAN),
];
