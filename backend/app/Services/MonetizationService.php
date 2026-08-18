<?php

namespace App\Services;

use App\Models\AppBranding;
use App\Models\Institution;
use App\Models\InstitutionAddonGrant;
use App\Models\InstitutionSubscription;
use App\Models\SubscriptionAddon;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MonetizationService
{
    public const CACHE_LAUNCHED_KEY = 'monetization.launched';

    public function isLaunched(): bool
    {
        return (bool) Cache::remember(self::CACHE_LAUNCHED_KEY, 60, function () {
            $row = AppBranding::query()->value('monetization_launched');
            if ($row === null) {
                return (bool) config('monetization.launched', false);
            }

            return (bool) $row;
        });
    }

    public function setLaunched(bool $launched): AppBranding
    {
        $branding = AppBranding::query()->firstOrCreate([], [
            'maintenance_mode' => false,
            'monetization_launched' => false,
            'default_storage_quota_mb' => (int) config('monetization.default_storage_quota_mb', 5120),
        ]);

        $branding->update([
            'monetization_launched' => $launched,
        ]);

        Cache::forget(self::CACHE_LAUNCHED_KEY);

        return $branding->fresh();
    }

    public function defaultStorageQuotaMb(): int
    {
        $fromDb = AppBranding::query()->value('default_storage_quota_mb');
        if ($fromDb !== null) {
            return (int) $fromDb;
        }

        return (int) config('monetization.default_storage_quota_mb', 5120);
    }

    public function setDefaultStorageQuotaMb(int $mb): AppBranding
    {
        $branding = AppBranding::query()->firstOrCreate([], [
            'maintenance_mode' => false,
            'monetization_launched' => false,
            'default_storage_quota_mb' => $mb,
        ]);

        $branding->update(['default_storage_quota_mb' => $mb]);

        return $branding->fresh();
    }

    /**
     * Ringkasan fitur untuk payload autentikasi / konteks institusi.
     * Saat belum diluncurkan: selalu tampilkan launched=false dan sembunyikan katalog.
     *
     * @return array<string, mixed>
     */
    public function featuresForInstitution(?Institution $institution): array
    {
        $launched = $this->isLaunched();

        if (! $launched || ! $institution) {
            return [
                'launched' => false,
                'store_visible' => false,
                'online_exam' => [
                    'entitled' => true,
                    'enforced' => false,
                ],
                'storage' => [
                    'quota_mb' => null,
                    'addon_mb' => 0,
                    'total_mb' => null,
                    'upgrade_visible' => false,
                ],
            ];
        }

        $storage = $this->resolveStorageQuota($institution);

        return [
            'launched' => true,
            'store_visible' => true,
            'online_exam' => [
                'entitled' => $this->isOnlineExamEntitled($institution),
                'enforced' => true,
            ],
            'storage' => [
                'quota_mb' => $storage['quota_mb'],
                'addon_mb' => $storage['addon_mb'],
                'total_mb' => $storage['total_mb'],
                'used_mb' => $this->usedStorageMb($institution),
                'upgrade_visible' => true,
            ],
            'plan' => $this->planSummary($institution),
            'addons' => $this->activeAddonKeys($institution),
        ];
    }

    /**
     * Sebelum launch: selalu true agar tidak merusak Beta yang sudah dipakai.
     * Setelah launch: plan includes_online_exam ATAU grant add-on online_exam.
     */
    public function isOnlineExamEntitled(Institution $institution): bool
    {
        if (! $this->isLaunched()) {
            return true;
        }

        $subscription = $institution->relationLoaded('subscription')
            ? $institution->subscription
            : $institution->subscription()->with('plan')->first();

        if ($subscription?->plan?->includes_online_exam) {
            return true;
        }

        return $this->hasActiveAddonGrant($institution, SubscriptionAddon::KEY_ONLINE_EXAM);
    }

    /**
     * @return array{quota_mb:int, addon_mb:int, total_mb:int}
     */
    public function resolveStorageQuota(Institution $institution): array
    {
        $quota = $institution->storage_quota_mb;
        if ($quota === null) {
            $subscription = $institution->relationLoaded('subscription')
                ? $institution->subscription
                : $institution->subscription()->with('plan')->first();

            $quota = $subscription?->plan?->storage_quota_mb ?? $this->defaultStorageQuotaMb();
        }

        $addonMb = (int) ($institution->storage_addon_mb ?? 0);

        $grant = $this->activeAddonGrant($institution, SubscriptionAddon::KEY_STORAGE_UPGRADE);
        if ($grant && $grant->storage_mb !== null) {
            $addonMb = max($addonMb, (int) $grant->storage_mb);
        }

        $quota = (int) $quota;
        $addonMb = (int) $addonMb;

        return [
            'quota_mb' => $quota,
            'addon_mb' => $addonMb,
            'total_mb' => $quota + $addonMb,
        ];
    }

    /**
     * Estimasi pemakaian disk per institusi (MB), dari path penyimpanan yang dikenal.
     */
    public function usedStorageMb(Institution $institution): float
    {
        $bytes = $this->usedStorageBytes((int) $institution->id);

        return round($bytes / (1024 * 1024), 2);
    }

    public function usedStorageBytes(int $institutionId): int
    {
        $total = 0;
        $roots = [
            ['public', "digital-archives/{$institutionId}"],
            ['public', "library/{$institutionId}"],
            ['local', "library/ebooks/{$institutionId}"],
            ['public', "student_documents"],
            ['public', "employee_documents"],
            ['public', "surat"],
            ['public', "kop"],
            ['public', "imports"],
            ['public', "ppdb"],
            ['public', "exam"],
            ['public', "uks"],
        ];

        foreach ($roots as [$disk, $path]) {
            try {
                if (! \Illuminate\Support\Facades\Storage::disk($disk)->exists($path)) {
                    continue;
                }
                $total += $this->directorySizeBytes($disk, $path, $institutionId);
            } catch (\Throwable) {
                // Abaikan path yang tidak bisa dibaca
            }
        }

        return $total;
    }

    /**
     * @return true|array{message:string,code:string,used_mb:float,quota_mb:int,incoming_mb:float}
     */
    public function assertCanStoreBytes(Institution $institution, int $incomingBytes): true|array
    {
        if (! $this->isLaunched() || $incomingBytes <= 0) {
            return true;
        }

        $quota = $this->resolveStorageQuota($institution);
        $quotaBytes = ((int) $quota['total_mb']) * 1024 * 1024;
        $used = $this->usedStorageBytes((int) $institution->id);

        if (($used + $incomingBytes) <= $quotaBytes) {
            return true;
        }

        return [
            'message' => 'Kuota penyimpanan sekolah penuh. Hapus file lama atau tingkatkan paket/add-on storage.',
            'code' => 'storage_quota_exceeded',
            'used_mb' => round($used / (1024 * 1024), 2),
            'quota_mb' => (int) $quota['total_mb'],
            'incoming_mb' => round($incomingBytes / (1024 * 1024), 2),
        ];
    }

    private function directorySizeBytes(string $disk, string $path, int $institutionId): int
    {
        $storage = \Illuminate\Support\Facades\Storage::disk($disk);
        $bytes = 0;

        // Path spesifik institusi: jumlahkan semua file di dalamnya
        if (str_contains($path, (string) $institutionId) || str_starts_with($path, 'digital-archives/') || str_starts_with($path, 'library/')) {
            foreach ($storage->allFiles($path) as $file) {
                $bytes += (int) $storage->size($file);
            }

            return $bytes;
        }

        // Path bersama: hanya hitung file yang path-nya memuat /{id}/ atau _{id}_
        $needle = '/'.$institutionId.'/';
        $needleAlt = '_'.$institutionId.'_';
        foreach ($storage->allFiles($path) as $file) {
            $normalized = '/'.ltrim(str_replace('\\', '/', $file), '/');
            if (str_contains($normalized, $needle) || str_contains($file, $needleAlt) || str_contains($file, '/'.$institutionId.'/')) {
                $bytes += (int) $storage->size($file);
            }
        }

        return $bytes;
    }

    public function hasActiveAddonGrant(Institution $institution, string $addonKey): bool
    {
        $grant = $this->activeAddonGrant($institution, $addonKey);

        return $grant !== null;
    }

    public function activeAddonGrant(Institution $institution, string $addonKey): ?InstitutionAddonGrant
    {
        $grants = $institution->relationLoaded('addonGrants')
            ? $institution->addonGrants
            : $institution->addonGrants()->get();

        /** @var InstitutionAddonGrant|null $grant */
        $grant = $grants->first(function (InstitutionAddonGrant $g) use ($addonKey) {
            return $g->addon_key === $addonKey && $g->isCurrentlyActive();
        });

        return $grant;
    }

    /**
     * @return list<string>
     */
    public function activeAddonKeys(Institution $institution): array
    {
        $grants = $institution->relationLoaded('addonGrants')
            ? $institution->addonGrants
            : $institution->addonGrants()->get();

        return $grants
            ->filter(fn (InstitutionAddonGrant $g) => $g->isCurrentlyActive())
            ->pluck('addon_key')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function planSummary(Institution $institution): ?array
    {
        $subscription = $institution->relationLoaded('subscription')
            ? $institution->subscription
            : $institution->subscription()->with('plan')->first();

        if (! $subscription) {
            return null;
        }

        return [
            'status' => $subscription->status,
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'plan' => $subscription->plan ? [
                'id' => $subscription->plan->id,
                'key' => $subscription->plan->key,
                'name' => $subscription->plan->name,
                'includes_online_exam' => (bool) $subscription->plan->includes_online_exam,
                'storage_quota_mb' => (int) $subscription->plan->storage_quota_mb,
            ] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsertInstitutionSubscription(Institution $institution, array $payload): InstitutionSubscription
    {
        return DB::transaction(function () use ($institution, $payload) {
            $planId = $payload['subscription_plan_id'] ?? null;
            $plan = $planId ? SubscriptionPlan::query()->find($planId) : null;

            $subscription = InstitutionSubscription::query()->updateOrCreate(
                ['institution_id' => $institution->id],
                [
                    'subscription_plan_id' => $plan?->id,
                    'status' => $payload['status'] ?? 'manual',
                    'starts_at' => $payload['starts_at'] ?? now(),
                    'ends_at' => $payload['ends_at'] ?? null,
                    'notes' => $payload['notes'] ?? null,
                ]
            );

            if ($plan) {
                $institution->update([
                    'storage_quota_mb' => $plan->storage_quota_mb,
                ]);
            }

            return $subscription->load('plan');
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function upsertAddonGrant(Institution $institution, string $addonKey, array $payload): InstitutionAddonGrant
    {
        return DB::transaction(function () use ($institution, $addonKey, $payload) {
            $catalog = SubscriptionAddon::query()->where('key', $addonKey)->first();
            $storageMb = array_key_exists('storage_mb', $payload)
                ? $payload['storage_mb']
                : $catalog?->storage_mb;

            $grant = InstitutionAddonGrant::query()->updateOrCreate(
                [
                    'institution_id' => $institution->id,
                    'addon_key' => $addonKey,
                ],
                [
                    'is_active' => (bool) ($payload['is_active'] ?? true),
                    'storage_mb' => $storageMb,
                    'starts_at' => $payload['starts_at'] ?? now(),
                    'ends_at' => $payload['ends_at'] ?? null,
                    'source' => $payload['source'] ?? 'manual',
                    'notes' => $payload['notes'] ?? null,
                ]
            );

            if ($addonKey === SubscriptionAddon::KEY_STORAGE_UPGRADE) {
                $addonTotal = $grant->isCurrentlyActive() ? (int) ($grant->storage_mb ?? 0) : 0;
                $institution->update(['storage_addon_mb' => $addonTotal]);
            }

            return $grant;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function platformSummary(): array
    {
        $branding = AppBranding::query()->first();

        return [
            'launched' => $this->isLaunched(),
            'default_storage_quota_mb' => $branding
                ? (int) ($branding->default_storage_quota_mb ?? $this->defaultStorageQuotaMb())
                : $this->defaultStorageQuotaMb(),
            'plans_count' => SubscriptionPlan::query()->count(),
            'active_plans_count' => SubscriptionPlan::query()->where('is_active', true)->count(),
            'addons_count' => SubscriptionAddon::query()->count(),
            'subscriptions_count' => InstitutionSubscription::query()->count(),
            'addon_grants_count' => InstitutionAddonGrant::query()->where('is_active', true)->count(),
        ];
    }
}
