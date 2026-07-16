<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleKey)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Support multiple modules separated by | (user needs access to any one)
        $keys = array_map('trim', explode('|', $moduleKey));
        $hasAccess = false;
        foreach ($keys as $key) {
            if ($key && $user->hasModuleAccess($key)) {
                $hasAccess = true;
                break;
            }
        }

        // Kepala Lab (penanggung jawab) may use facility/inventory/schedule APIs
        // for managing their assigned labs without full module grants.
        if (!$hasAccess) {
            $labModules = ['facility', 'inventory', 'schedule'];
            $needsLabBypass = count(array_intersect($keys, $labModules)) > 0;
            if ($needsLabBypass && $user->isLabResponsible()) {
                $hasAccess = true;
            }
        }

        // Pembina ekskul may use extracurricular APIs for supervised clubs
        // even before permission sync / re-login.
        if (!$hasAccess && in_array('extracurricular', $keys, true) && $user->isExtracurricularSupervisor()) {
            $hasAccess = true;
        }

        // Guru terjadwal piket boleh akses API modul (lapor kejadian / log)
        // meskipun permission belum tersync ke session.
        if (!$hasAccess) {
            $needsPiketBypass = count(array_intersect($keys, ['guru_piket', 'guru_piket_manage'])) > 0;
            if ($needsPiketBypass && \App\Support\PiketAccess::canAccess($user)) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
