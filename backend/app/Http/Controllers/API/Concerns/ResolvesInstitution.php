<?php

namespace App\Http\Controllers\API\Concerns;

use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesInstitution
{
    /**
     * Resolve institution ID for the current request (multi-tenant scope).
     * - Guest: null
     * - Super Admin / admin with institution_id in request: that value (if allowed)
     * - Others: active institution context (header/cookie), fallback home
     */
    protected function resolveInstitutionId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        return InstitutionContext::resolveForUser(
            $user,
            $request,
            $request->filled('institution_id') ? $request->get('institution_id') : null
        );
    }

    /**
     * Fail-closed object-level tenant check (BOLA).
     * Platform admins may access any institution; others must canAccess the record's institution.
     */
    protected function denyUnlessCanAccessInstitution(
        Request $request,
        ?int $recordInstitutionId,
        string $message = 'Akses ditolak.'
    ): ?JsonResponse {
        $user = $request->user();
        if (!$user || $recordInstitutionId === null || $recordInstitutionId <= 0) {
            return response()->json(['message' => $message], 403);
        }

        if ($user->isAdminOrSuperAdmin()) {
            return null;
        }

        if (! InstitutionContext::canAccessInstitution($user, (int) $recordInstitutionId)) {
            return response()->json(['message' => $message], 403);
        }

        return null;
    }

    /**
     * Fail-closed: record must belong to the currently resolved institution.
     * Prefer denyUnlessCanAccessInstitution when multi-school affiliation is enough;
     * use this when the module scopes strictly to the active institution context.
     */
    protected function denyUnlessSameInstitution(
        Request $request,
        ?int $recordInstitutionId,
        string $message = 'Akses ditolak.'
    ): ?JsonResponse {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => $message], 403);
        }

        if ($user->isAdminOrSuperAdmin()) {
            $activeId = $this->resolveInstitutionId($request);
            // Platform admin without an explicit/active institution may operate cross-tenant.
            if ($activeId === null) {
                return null;
            }

            if ($recordInstitutionId === null || (int) $recordInstitutionId !== (int) $activeId) {
                return response()->json(['message' => $message], 403);
            }

            return null;
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (
            $institutionId === null
            || $recordInstitutionId === null
            || (int) $recordInstitutionId !== (int) $institutionId
        ) {
            return response()->json(['message' => $message], 403);
        }

        return null;
    }
}
