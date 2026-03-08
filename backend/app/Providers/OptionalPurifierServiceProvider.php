<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Mendaftarkan Mews\Purifier hanya jika paket terpasang (vendor ada).
 * Menghindari error "Class PurifierServiceProvider not found" saat deploy tanpa vendor atau cache lama.
 */
class OptionalPurifierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (class_exists(\Mews\Purifier\PurifierServiceProvider::class)) {
            $this->app->register(\Mews\Purifier\PurifierServiceProvider::class);
        }
    }
}
