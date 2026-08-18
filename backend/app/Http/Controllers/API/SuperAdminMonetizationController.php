<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Institution;
use App\Models\InstitutionAddonGrant;
use App\Models\SubscriptionAddon;
use App\Models\SubscriptionPlan;
use App\Services\MonetizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SuperAdminMonetizationController extends Controller
{
    public function __construct(private MonetizationService $monetization)
    {
    }

    private function ensureSuperAdmin(Request $request): void
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
    }

    public function summary(Request $request)
    {
        $this->ensureSuperAdmin($request);

        return response()->json([
            'data' => $this->monetization->platformSummary(),
        ]);
    }

    public function updateLaunch(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'launched' => 'required|boolean',
            'default_storage_quota_mb' => 'nullable|integer|min:100|max:1048576',
        ]);

        try {
            $before = $this->monetization->platformSummary();
            $branding = $this->monetization->setLaunched((bool) $validated['launched']);

            if (array_key_exists('default_storage_quota_mb', $validated) && $validated['default_storage_quota_mb'] !== null) {
                $branding = $this->monetization->setDefaultStorageQuotaMb((int) $validated['default_storage_quota_mb']);
            }

            $after = $this->monetization->platformSummary();

            AuditLog::logManual(
                $request,
                'monetization.launch_updated',
                get_class($branding),
                $branding->id,
                $before,
                $after
            );

            return response()->json([
                'message' => $after['launched']
                    ? 'Monetisasi ditampilkan ke sekolah'
                    : 'Monetisasi disembunyikan dari sekolah',
                'data' => $after,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update monetization launch', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui status peluncuran monetisasi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function plans(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $plans = SubscriptionPlan::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $plans]);
    }

    public function storePlan(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'key' => 'required|string|max:64|alpha_dash|unique:subscription_plans,key',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'storage_quota_mb' => 'required|integer|min:100|max:1048576',
            'includes_online_exam' => 'boolean',
            'price_monthly' => 'nullable|integer|min:0',
            'price_yearly' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        $plan = SubscriptionPlan::create([
            ...$validated,
            'includes_online_exam' => (bool) ($validated['includes_online_exam'] ?? false),
            'price_monthly' => (int) ($validated['price_monthly'] ?? 0),
            'price_yearly' => (int) ($validated['price_yearly'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        AuditLog::logManual($request, 'monetization.plan_created', SubscriptionPlan::class, $plan->id, null, $plan->toArray());

        return response()->json(['message' => 'Paket dibuat', 'data' => $plan], 201);
    }

    public function updatePlan(Request $request, int $id)
    {
        $this->ensureSuperAdmin($request);

        $plan = SubscriptionPlan::query()->findOrFail($id);

        $validated = $request->validate([
            'key' => ['sometimes', 'string', 'max:64', 'alpha_dash', Rule::unique('subscription_plans', 'key')->ignore($plan->id)],
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'storage_quota_mb' => 'sometimes|integer|min:100|max:1048576',
            'includes_online_exam' => 'boolean',
            'price_monthly' => 'nullable|integer|min:0',
            'price_yearly' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        $before = $plan->toArray();
        $plan->update($validated);

        AuditLog::logManual($request, 'monetization.plan_updated', SubscriptionPlan::class, $plan->id, $before, $plan->fresh()->toArray());

        return response()->json(['message' => 'Paket diperbarui', 'data' => $plan->fresh()]);
    }

    public function addons(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $addons = SubscriptionAddon::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $addons]);
    }

    public function updateAddon(Request $request, int $id)
    {
        $this->ensureSuperAdmin($request);

        $addon = SubscriptionAddon::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'storage_mb' => 'nullable|integer|min:0|max:1048576',
            'price_monthly' => 'nullable|integer|min:0',
            'price_yearly' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
        ]);

        $before = $addon->toArray();
        $addon->update($validated);

        AuditLog::logManual($request, 'monetization.addon_updated', SubscriptionAddon::class, $addon->id, $before, $addon->fresh()->toArray());

        return response()->json(['message' => 'Add-on diperbarui', 'data' => $addon->fresh()]);
    }

    public function institutions(Request $request)
    {
        $this->ensureSuperAdmin($request);

        $search = trim((string) $request->get('search', ''));
        $perPage = min((int) $request->get('per_page', 20), 100);

        $query = Institution::query()
            ->with([
                'subscription.plan:id,key,name,includes_online_exam,storage_quota_mb',
                'addonGrants',
            ])
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('npsn', 'like', '%' . $search . '%');
            });
        }

        $page = $query->paginate($perPage);

        $data = $page->getCollection()->map(function (Institution $inst) {
            $storage = $this->monetization->resolveStorageQuota($inst);

            return [
                'id' => $inst->id,
                'name' => $inst->name,
                'npsn' => $inst->npsn,
                'is_active' => (bool) $inst->is_active,
                'storage' => $storage,
                'online_exam_entitled' => $this->monetization->isOnlineExamEntitled($inst),
                'subscription' => $this->monetization->planSummary($inst),
                'addon_grants' => $inst->addonGrants->map(fn (InstitutionAddonGrant $g) => [
                    'id' => $g->id,
                    'addon_key' => $g->addon_key,
                    'is_active' => (bool) $g->is_active,
                    'currently_active' => $g->isCurrentlyActive(),
                    'storage_mb' => $g->storage_mb,
                    'source' => $g->source,
                    'starts_at' => $g->starts_at?->toIso8601String(),
                    'ends_at' => $g->ends_at?->toIso8601String(),
                    'notes' => $g->notes,
                ])->values(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function upsertInstitutionSubscription(Request $request, int $institutionId)
    {
        $this->ensureSuperAdmin($request);

        $institution = Institution::query()->findOrFail($institutionId);

        $validated = $request->validate([
            'subscription_plan_id' => 'nullable|integer|exists:subscription_plans,id',
            'status' => 'nullable|string|in:manual,trial,active,suspended',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'notes' => 'nullable|string|max:2000',
        ]);

        $subscription = $this->monetization->upsertInstitutionSubscription($institution, $validated);

        AuditLog::logManual(
            $request,
            'monetization.subscription_upserted',
            Institution::class,
            $institution->id,
            null,
            $subscription->toArray()
        );

        return response()->json([
            'message' => 'Langganan institusi disimpan',
            'data' => $this->monetization->planSummary($institution->fresh(['subscription.plan'])),
        ]);
    }

    public function upsertInstitutionAddon(Request $request, int $institutionId)
    {
        $this->ensureSuperAdmin($request);

        $institution = Institution::query()->findOrFail($institutionId);

        $validated = $request->validate([
            'addon_key' => ['required', 'string', Rule::in([
                SubscriptionAddon::KEY_STORAGE_UPGRADE,
                SubscriptionAddon::KEY_ONLINE_EXAM,
            ])],
            'is_active' => 'boolean',
            'storage_mb' => 'nullable|integer|min:0|max:1048576',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'source' => 'nullable|string|in:manual,trial,paid',
            'notes' => 'nullable|string|max:2000',
        ]);

        $grant = $this->monetization->upsertAddonGrant(
            $institution,
            $validated['addon_key'],
            $validated
        );

        AuditLog::logManual(
            $request,
            'monetization.addon_grant_upserted',
            Institution::class,
            $institution->id,
            null,
            $grant->toArray()
        );

        return response()->json([
            'message' => 'Grant add-on disimpan',
            'data' => $grant,
        ]);
    }
}
