<?php

namespace App\Http\Middleware;

use App\Services\MonetizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokir endpoint yang hanya boleh dipakai sekolah setelah monetisasi diluncurkan.
 * Super admin tidak dicegat di sini (route SA memakai middleware sendiri).
 */
class EnsureMonetizationLaunched
{
    public function __construct(private MonetizationService $monetization)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->monetization->isLaunched()) {
            return response()->json([
                'message' => 'Modul monetisasi belum diluncurkan.',
            ], 404);
        }

        return $next($request);
    }
}
