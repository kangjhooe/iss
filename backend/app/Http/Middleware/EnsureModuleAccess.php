<?php

namespace App\Http\Middleware;

use App\Support\InstitutionContext;
use App\Support\InstitutionModuleVisibility;
use App\Support\PiketAccess;
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

        if (! $user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $activeInstitutionId = InstitutionContext::resolveActiveInstitutionId($user, $request);

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
        // Scoped to active institution so duties at school A don't unlock APIs at school B.
        if (! $hasAccess) {
            $labModules = ['facility', 'inventory', 'schedule'];
            $needsLabBypass = count(array_intersect($keys, $labModules)) > 0;
            if ($needsLabBypass && $user->isLabResponsible($activeInstitutionId)) {
                $hasAccess = true;
            }
        }

        // Pembina ekskul may use extracurricular APIs for supervised clubs
        // even before permission sync / re-login.
        if (! $hasAccess && in_array('extracurricular', $keys, true)
            && $user->isExtracurricularSupervisor($activeInstitutionId)) {
            $hasAccess = true;
        }

        // Guru terjadwal piket boleh akses API modul (lapor kejadian / log)
        // meskipun permission belum tersync ke session — hanya di sekolah aktif.
        if (! $hasAccess) {
            $needsPiketBypass = count(array_intersect($keys, ['guru_piket', 'guru_piket_manage'])) > 0;
            if ($needsPiketBypass && PiketAccess::isScheduled($user, $activeInstitutionId)) {
                $hasAccess = true;
            }
        }

        if ($hasAccess && ! $user->isSuperAdmin()) {
            $anyVisible = false;
            foreach ($keys as $key) {
                if ($key && InstitutionModuleVisibility::isVisible($activeInstitutionId, $key)) {
                    $anyVisible = true;
                    break;
                }
            }
            if (! $anyVisible) {
                $hasAccess = false;
            }
        }

        if (! $hasAccess) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
