<?php

namespace App\Http\Controllers\API\Concerns;

use App\Models\InventoryItem;
use App\Support\InstitutionContext;
use App\Support\InventoryAccess;
use Illuminate\Http\Request;

trait ResolvesActiveInstitution
{
    protected function resolveInstitutionId(Request $request): ?int
    {
        $user = $request->user();
        if ($user->isSuperAdmin()) {
            $id = $request->get('institution_id');

            return $id !== null && $id !== '' ? (int) $id : null;
        }

        if ($user->isAdmin() || $user->isInstitutionAdmin()) {
            return InstitutionContext::resolveForUser(
                $user,
                $request,
                $request->get('institution_id')
            ) ?? ($user->institution_id ? (int) $user->institution_id : null);
        }

        return InstitutionContext::resolveForUser($user, $request, null);
    }

    protected function canAccessInstitutionRecord(Request $request, int $institutionId): bool
    {
        return InstitutionContext::canAccessInstitution($request->user(), $institutionId);
    }

    protected function userCanAccessInventoryItem(Request $request, InventoryItem $item): bool
    {
        return InventoryAccess::canAccessItem($request->user(), $item);
    }

    protected function userCanAccessInventoryAsset(Request $request, \App\Models\InventoryAsset $asset): bool
    {
        return InventoryAccess::canAccessAsset($request->user(), $asset);
    }

    protected function denyUnlessInventoryManage(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (! InventoryAccess::canManage($request->user())) {
            return InventoryAccess::forbiddenManageResponse();
        }

        return null;
    }

    protected function denyUnlessInventoryReports(Request $request): ?\Illuminate\Http\JsonResponse
    {
        if (! InventoryAccess::canManage($request->user())) {
            return InventoryAccess::forbiddenReportsResponse();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function inventoryFiltersWithRoomScope(Request $request, array $filters, ?int $institutionId): array
    {
        if ($institutionId) {
            InventoryAccess::applyReportFilters($filters, $request->user(), $institutionId);
        }

        return $filters;
    }
}
