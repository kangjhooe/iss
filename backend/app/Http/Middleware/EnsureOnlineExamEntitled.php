<?php

namespace App\Http\Middleware;

use App\Models\Institution;
use App\Services\MonetizationService;
use App\Support\InstitutionContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokir akses modul ujian online jika monetisasi sudah diluncurkan
 * dan institusi tidak punya entitlement (paket / add-on).
 */
class EnsureOnlineExamEntitled
{
    public function __construct(private MonetizationService $monetization)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->monetization->isLaunched()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && $user->isAdminOrSuperAdmin()) {
            return $next($request);
        }

        $institutionId = InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->get('institution_id')
        );

        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $institution = Institution::query()->find($institutionId);
        if (! $institution || ! $this->monetization->isOnlineExamEntitled($institution)) {
            return response()->json([
                'message' => 'Modul Ujian Online tidak termasuk paket/add-on aktif sekolah Anda.',
                'code' => 'online_exam_not_entitled',
            ], 403);
        }

        return $next($request);
    }
}
