<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login: batasi per kredensial+IP (bukan IP saja) agar satu jaringan/NAT
        // atau storm request lain tidak memblokir percobaan login pertama.
        RateLimiter::for('login', function (Request $request) {
            $login = Str::lower(trim((string) $request->input('login', '')));
            $ip = (string) $request->ip();

            return [
                Limit::perMinute(8)->by($login !== '' ? $login.'|'.$ip : $ip),
                Limit::perMinute(40)->by('login-ip:'.$ip),
            ];
        });

        // Refresh: longgar, karena bootstrap SPA memanggil /me lalu /refresh-token.
        RateLimiter::for('auth-refresh', function (Request $request) {
            return Limit::perMinute(60)->by('refresh-ip:'.$request->ip());
        });

        RateLimiter::for('auth-public', function (Request $request) {
            return Limit::perMinute(10)->by('auth-public:'.$request->ip());
        });
    }
}
