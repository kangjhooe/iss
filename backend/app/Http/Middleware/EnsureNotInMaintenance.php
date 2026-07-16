<?php

namespace App\Http\Middleware;

use App\Models\AppBranding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInMaintenance
{
    /**
     * Paths that remain reachable during maintenance (relative to /api/v1).
     */
    private array $allowExact = [
        'login',
        'logout',
        'refresh-token',
        'forgot-password',
        'reset-password',
        'app-branding',
        'me',
        'super-admin/impersonate/stop',
        'app-branding/maintenance',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isMaintenanceEnabled()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        // Super admin yang sedang impersonate tetap diizinkan (cookie impersonator valid)
        if ($request->cookie(\App\Http\Middleware\AddTokenFromCookie::COOKIE_IMPERSONATOR)) {
            return $next($request);
        }

        $path = $this->normalizePath($request);
        foreach ($this->allowExact as $allowed) {
            if ($path === $allowed) {
                return $next($request);
            }
        }

        // Public prefixes (PPDB, public exams, etc.)
        if (str_starts_with($path, 'public/') || str_starts_with($path, 'ppdb/public')) {
            return $next($request);
        }

        $message = $this->maintenanceMessage();

        return response()->json([
            'message' => $message,
            'maintenance' => true,
        ], 503);
    }

    private function normalizePath(Request $request): string
    {
        $path = trim($request->path(), '/');
        if (str_starts_with($path, 'api/v1/')) {
            $path = substr($path, 7);
        } elseif (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }
        return $path;
    }

    private function isMaintenanceEnabled(): bool
    {
        try {
            if (!Schema::hasTable('app_branding') || !Schema::hasColumn('app_branding', 'maintenance_mode')) {
                return false;
            }
            return (bool) AppBranding::query()->value('maintenance_mode');
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function maintenanceMessage(): string
    {
        try {
            $msg = AppBranding::query()->value('maintenance_message');
            if (is_string($msg) && trim($msg) !== '') {
                return $msg;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return 'Sistem sedang dalam mode pemeliharaan. Silakan coba lagi nanti.';
    }
}
