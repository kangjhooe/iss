<?php

namespace App\Support;

use App\Models\Institution;
use App\Models\Permission;

/**
 * Modul yang sengaja disembunyikan admin sekolah (seluruh tenant),
 * terpisah dari permission per guru.
 */
class InstitutionModuleVisibility
{
    /**
     * @return list<string>
     */
    public static function hiddenKeys(?int $institutionId): array
    {
        if (! $institutionId) {
            return [];
        }

        $request = request();
        $cacheKey = 'hidden_module_keys_'.$institutionId;
        if ($request && $request->attributes->has($cacheKey)) {
            return $request->attributes->get($cacheKey);
        }

        $raw = Institution::query()->where('id', $institutionId)->value('hidden_module_keys');
        $keys = self::normalize($raw);
        $request?->attributes->set($cacheKey, $keys);

        return $keys;
    }

    public static function isHidden(?int $institutionId, string $moduleKey): bool
    {
        if ($moduleKey === '') {
            return false;
        }

        return in_array($moduleKey, self::hiddenKeys($institutionId), true);
    }

    public static function isVisible(?int $institutionId, string $moduleKey): bool
    {
        return ! self::isHidden($institutionId, $moduleKey);
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function filterVisibleKeys(?int $institutionId, array $keys): array
    {
        $hidden = self::hiddenKeys($institutionId);
        if ($hidden === []) {
            return array_values($keys);
        }

        return array_values(array_filter(
            $keys,
            fn (string $key) => ! in_array($key, $hidden, true)
        ));
    }

    /**
     * @param  list<string>  $keys
     * @return list<string>
     */
    public static function sanitizeHiddenKeys(?int $institutionId, array $keys): array
    {
        $normalized = self::normalize($keys);
        if ($normalized === []) {
            return [];
        }

        $valid = Permission::query()->whereIn('key', $normalized)->pluck('key')->all();

        return VocationalAccess::filterPermissionKeysForInstitution($institutionId, $valid);
    }

    /**
     * @return list<string>
     */
    public static function normalize(mixed $raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $keys = [];
        foreach ($raw as $key) {
            if (! is_string($key)) {
                continue;
            }
            $trimmed = trim($key);
            if ($trimmed !== '') {
                $keys[] = $trimmed;
            }
        }

        return array_values(array_unique($keys));
    }
}
