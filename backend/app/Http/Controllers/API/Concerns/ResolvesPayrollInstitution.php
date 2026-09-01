<?php

namespace App\Http\Controllers\API\Concerns;

use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesPayrollInstitution
{
    protected function payrollInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    protected function denyPayrollForeign(Request $request, int $modelInstitutionId): ?JsonResponse
    {
        $institutionId = $this->payrollInstitutionId($request);
        if ($request->user()->isSuperAdmin()) {
            return null;
        }
        if (! $institutionId || (int) $modelInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return null;
    }
}
