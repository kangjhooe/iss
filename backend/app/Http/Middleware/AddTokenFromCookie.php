<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan token dari httpOnly cookie ke header Authorization
 * sehingga Sanctum dapat mengautentikasi request tanpa token di localStorage.
 */
class AddTokenFromCookie
{
    public const COOKIE_AUTH = 'auth_token';
    public const COOKIE_REFRESH = 'refresh_token';
    public const COOKIE_IMPERSONATOR = 'impersonator_token';
    public const COOKIE_IMPERSONATOR_REFRESH = 'impersonator_refresh_token';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('Authorization')) {
            return $next($request);
        }

        $token = $request->cookie(self::COOKIE_AUTH);
        if ($token) {
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}
