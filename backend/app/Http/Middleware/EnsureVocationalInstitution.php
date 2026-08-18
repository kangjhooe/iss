<?php

namespace App\Http\Middleware;

use App\Support\InstitutionContext;
use App\Support\VocationalAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blokir PKL / BKK / mitra DU/DI (dan fitur kejuruan lain) di luar SMK/MAK.
 */
class EnsureVocationalInstitution
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $institutionId = InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->get('institution_id')
        );

        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        if (! VocationalAccess::isVocationalInstitution($institutionId)) {
            return response()->json([
                'message' => 'Fitur ini hanya berlaku untuk institusi SMK/MAK.',
                'code' => 'vocational_only',
            ], 422);
        }

        return $next($request);
    }
}
