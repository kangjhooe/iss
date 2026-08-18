<?php

namespace App\Support;

use App\Models\AdditionalDuty;
use App\Models\Institution;

/**
 * Fitur kejuruan (PKL, BKK, program keahlian, duty Hubin/Kaprog/Bengkel)
 * hanya untuk SMK dan MAK.
 */
class VocationalAccess
{
    public const LEVELS = ['SMK', 'MAK'];

    public const PERMISSION_KEYS = ['pkl', 'bkk'];

    public const DUTY_KEYS = [
        'kepala_program_keahlian',
        'kepala_bengkel',
        'koordinator_hubin',
        'koordinator_pkl',
        'koordinator_bkk',
    ];

    public static function isVocationalLevel(?string $level): bool
    {
        return in_array($level, self::LEVELS, true);
    }

    public static function isVocationalInstitution(?int $institutionId): bool
    {
        if (! $institutionId) {
            return false;
        }

        $level = Institution::query()->where('id', $institutionId)->value('level');

        return self::isVocationalLevel($level);
    }

    /**
     * @param  array<int, int|string>  $dutyIds
     * @return array<int, int>
     */
    public static function filterDutyIdsForInstitution(?int $institutionId, array $dutyIds): array
    {
        $ids = array_values(array_map('intval', $dutyIds));
        if (self::isVocationalInstitution($institutionId)) {
            return $ids;
        }

        $vocationalIds = AdditionalDuty::query()
            ->whereIn('key', self::DUTY_KEYS)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_filter($ids, fn (int $id) => ! in_array($id, $vocationalIds, true)));
    }

    /**
     * @param  list<string>  $permissionKeys
     * @return list<string>
     */
    public static function filterPermissionKeysForInstitution(?int $institutionId, array $permissionKeys): array
    {
        $keys = array_values(array_filter($permissionKeys));
        if (self::isVocationalInstitution($institutionId)) {
            return $keys;
        }

        return array_values(array_filter(
            $keys,
            fn (string $key) => ! in_array($key, self::PERMISSION_KEYS, true)
        ));
    }
}
