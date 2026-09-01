<?php

namespace App\Support;

use App\Models\InventoryDisposal;
use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Hak akses inventaris: modul penuh vs penanggung jawab ruangan (scope room_id).
 */
class InventoryAccess
{
    /** Tab inventaris yang boleh diakses PJ ruangan tanpa modul inventory penuh */
    public const ROOM_SCOPED_TABS = [
        'dashboard',
        'items',
        'assets',
        'loans',
        'maintenances',
    ];

    /** @deprecated Gunakan ROOM_SCOPED_TABS */
    public const LAB_SCOPED_TABS = self::ROOM_SCOPED_TABS;

    public static function canAccessModule(User $user, ?int $institutionId = null): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        if ($user->hasModuleAccess('inventory')) {
            return true;
        }

        return $institutionId !== null && $user->isRoomResponsible($institutionId);
    }

    /** CRUD penuh, kategori, laporan, import/export */
    public static function canManage(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        return $user->hasModuleAccess('inventory');
    }

    public static function forbiddenManageResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'Hanya petugas inventaris penuh yang dapat melakukan tindakan ini',
        ], 403);
    }

    public static function forbiddenReportsResponse(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'Laporan inventaris hanya untuk petugas inventaris penuh',
        ], 403);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function applyReportFilters(array &$filters, User $user, int $institutionId): void
    {
        self::applyRoomFilter($filters, $user, $institutionId);
    }

    public static function scopeTransactions(Builder $query, User $user, int $institutionId): Builder
    {
        if (! self::shouldScopeToRooms($user, $institutionId)) {
            return $query;
        }

        $ids = self::managedRoomIds($user, $institutionId);
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('item', fn ($iq) => $iq->whereIn('room_id', $ids));
    }

    public static function scopeAssetMovements(Builder $query, User $user, int $institutionId): Builder
    {
        if (! self::shouldScopeToRooms($user, $institutionId)) {
            return $query;
        }

        $ids = self::managedRoomIds($user, $institutionId);
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($ids) {
            $q->whereIn('from_room_id', $ids)
                ->orWhereIn('to_room_id', $ids);
        });
    }

    public static function shouldScopeToRooms(User $user, ?int $institutionId): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return false;
        }

        if ($user->hasModuleAccess('inventory')) {
            return false;
        }

        return $institutionId !== null && $user->isRoomResponsible($institutionId);
    }

    /**
     * @return list<int>
     */
    public static function managedRoomIds(User $user, int $institutionId): array
    {
        return $user->managedRoomIds($institutionId);
    }

    public static function canAssignRoom(User $user, int $institutionId, ?int $roomId): bool
    {
        if (self::canManage($user)) {
            return true;
        }

        if (! self::shouldScopeToRooms($user, $institutionId)) {
            return true;
        }

        if ($roomId === null || $roomId === 0) {
            return false;
        }

        return in_array($roomId, self::managedRoomIds($user, $institutionId), true);
    }

    /**
     * Terapkan filter ruangan ke array filter repository (room_id / room_ids).
     *
     * @param  array<string, mixed>  $filters
     */
    public static function applyRoomFilter(array &$filters, User $user, int $institutionId): void
    {
        if (! self::shouldScopeToRooms($user, $institutionId)) {
            return;
        }

        $managedIds = self::managedRoomIds($user, $institutionId);

        if (! empty($filters['room_id'])) {
            if (! in_array((int) $filters['room_id'], $managedIds, true)) {
                unset($filters['room_id']);
                $filters['room_ids'] = [0];
            }

            return;
        }

        $filters['room_ids'] = $managedIds !== [] ? $managedIds : [0];
    }

    public static function scopeItems(Builder $query, User $user, int $institutionId): Builder
    {
        if (! self::shouldScopeToRooms($user, $institutionId)) {
            return $query;
        }

        $ids = self::managedRoomIds($user, $institutionId);
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('room_id', $ids);
    }

    public static function scopeRoomLinkedRecords(Builder $query, User $user, int $institutionId): Builder
    {
        if (! self::shouldScopeToRooms($user, $institutionId)) {
            return $query;
        }

        $ids = self::managedRoomIds($user, $institutionId);
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($ids) {
            $q->whereHas('item', fn ($iq) => $iq->whereIn('room_id', $ids))
                ->orWhereHas('asset', fn ($aq) => $aq->whereIn('room_id', $ids));
        });
    }

    public static function scopeLoans(Builder $query, User $user, int $institutionId): Builder
    {
        return self::scopeRoomLinkedRecords($query, $user, $institutionId);
    }

    public static function scopeMaintenances(Builder $query, User $user, int $institutionId): Builder
    {
        return self::scopeRoomLinkedRecords($query, $user, $institutionId);
    }

    public static function scopeDisposals(Builder $query, User $user, int $institutionId): Builder
    {
        return self::scopeRoomLinkedRecords($query, $user, $institutionId);
    }

    public static function canAccessItem(User $user, InventoryItem $item): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if (! InstitutionContext::canAccessInstitution($user, (int) $item->institution_id)) {
            return false;
        }

        if ($user->isInstitutionAdmin() || $user->hasModuleAccess('inventory')) {
            return true;
        }

        $institutionId = (int) $item->institution_id;
        if ($user->isRoomResponsible($institutionId) && $item->room_id) {
            return in_array(
                (int) $item->room_id,
                self::managedRoomIds($user, $institutionId),
                true
            );
        }

        return false;
    }

    public static function canAccessDisposal(User $user, InventoryDisposal $disposal): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if (! InstitutionContext::canAccessInstitution($user, (int) $disposal->institution_id)) {
            return false;
        }

        if ($user->isInstitutionAdmin() || $user->hasModuleAccess('inventory')) {
            return true;
        }

        $disposal->loadMissing(['item', 'asset']);

        $roomId = $disposal->asset?->room_id ?? $disposal->item?->room_id;
        if ($roomId && $user->isRoomResponsible((int) $disposal->institution_id)) {
            return in_array(
                (int) $roomId,
                self::managedRoomIds($user, (int) $disposal->institution_id),
                true
            );
        }

        return false;
    }

    public static function canAccessAsset(User $user, \App\Models\InventoryAsset $asset): bool
    {
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        if (! InstitutionContext::canAccessInstitution($user, (int) $asset->institution_id)) {
            return false;
        }

        if ($user->isInstitutionAdmin() || $user->hasModuleAccess('inventory')) {
            return true;
        }

        $asset->loadMissing('item');
        if ($asset->item && self::canAccessItem($user, $asset->item)) {
            return true;
        }

        $institutionId = (int) $asset->institution_id;
        $roomId = $asset->room_id ?? $asset->item?->room_id;
        if ($roomId && $user->isRoomResponsible($institutionId)) {
            return in_array((int) $roomId, self::managedRoomIds($user, $institutionId), true);
        }

        return false;
    }
}
