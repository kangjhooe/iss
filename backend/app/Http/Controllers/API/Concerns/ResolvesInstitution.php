<?php

namespace App\Http\Controllers\API\Concerns;

use Illuminate\Http\Request;

trait ResolvesInstitution
{
    /**
     * Resolve institution ID for the current request (multi-tenant scope).
     * - Guest: null
     * - Super Admin with institution_id in request: that value
     * - Other users: their institution_id
     */
    protected function resolveInstitutionId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }
        if ($user->isSuperAdmin() && $request->filled('institution_id')) {
            return (int) $request->get('institution_id');
        }
        return $user->institution_id;
    }
}
