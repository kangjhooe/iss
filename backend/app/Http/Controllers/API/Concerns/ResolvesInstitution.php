<?php

namespace App\Http\Controllers\API\Concerns;

use App\Support\InstitutionContext;
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
}
