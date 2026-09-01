<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokir akses dokumentasi Swagger/OpenAPI saat L5_SWAGGER_ENABLED=false (default di production).
 */
class EnsureSwaggerDocumentationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('l5-swagger.enabled', false)) {
            abort(404);
        }

        return $next($request);
    }
}
