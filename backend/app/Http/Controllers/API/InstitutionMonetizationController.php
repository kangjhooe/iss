<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\SubscriptionAddon;
use App\Models\SubscriptionPlan;
use App\Services\MonetizationService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;

/**
 * Endpoint sisi sekolah — hanya aktif setelah Super Admin meluncurkan monetisasi.
 * Route dilindungi middleware EnsureMonetizationLaunched (404 saat dark launch).
 */
class InstitutionMonetizationController extends Controller
{
    public function __construct(private MonetizationService $monetization)
    {
    }

    public function overview(Request $request)
    {
        $user = $request->user();
        if (! $user || $user->isSuperAdmin()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (! in_array($user->role, ['admin', 'institution_admin'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $institutionId = InstitutionContext::resolveActiveInstitutionId($user, $request);
        if (! $institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
        }

        $institution = Institution::query()
            ->with(['subscription.plan', 'addonGrants'])
            ->find($institutionId);

        if (! $institution) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
        }

        return response()->json([
            'data' => [
                'features' => $this->monetization->featuresForInstitution($institution),
                'plans' => SubscriptionPlan::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'key', 'name', 'description', 'storage_quota_mb', 'includes_online_exam', 'price_monthly', 'price_yearly']),
                'addons' => SubscriptionAddon::query()
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get(['id', 'key', 'name', 'description', 'storage_mb', 'price_monthly', 'price_yearly']),
            ],
        ]);
    }
}
