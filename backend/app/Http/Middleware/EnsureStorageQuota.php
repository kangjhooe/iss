<?php

namespace App\Http\Middleware;

use App\Models\Institution;
use App\Services\MonetizationService;
use App\Support\InstitutionContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak upload jika institusi melebihi kuota penyimpanan (hanya saat monetisasi launched).
 */
class EnsureStorageQuota
{
    public function __construct(private MonetizationService $monetization)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->monetization->isLaunched()) {
            return $next($request);
        }

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        if (! $request->allFiles()) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || $user->isAdminOrSuperAdmin()) {
            return $next($request);
        }

        $institutionId = InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->get('institution_id')
        );
        if (! $institutionId) {
            return $next($request);
        }

        $incoming = 0;
        foreach ($request->allFiles() as $file) {
            if (is_array($file)) {
                foreach ($file as $f) {
                    if ($f) {
                        $incoming += (int) $f->getSize();
                    }
                }
            } elseif ($file) {
                $incoming += (int) $file->getSize();
            }
        }

        if ($incoming <= 0) {
            return $next($request);
        }

        $institution = Institution::query()->find($institutionId);
        if (! $institution) {
            return $next($request);
        }

        $check = $this->monetization->assertCanStoreBytes($institution, $incoming);
        if ($check !== true) {
            return response()->json($check, 422);
        }

        return $next($request);
    }
}
