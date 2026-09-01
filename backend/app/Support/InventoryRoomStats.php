<?php

namespace App\Support;

use App\Models\InventoryAsset;
use App\Models\InventoryItem;
use App\Models\InventoryLoan;
use App\Models\InventoryMaintenance;
use Illuminate\Support\Collection;

final class InventoryRoomStats
{
    public static function inventoryCountForRoom(int $roomId): int
    {
        return self::inventoryCountsByRoom([$roomId])->get($roomId, 0);
    }

    public static function damagedCountForRoom(int $roomId): int
    {
        return self::damagedCountsByRoom([$roomId])->get($roomId, 0);
    }

    public static function activeLoansForRoom(int $roomId): int
    {
        return InventoryLoan::query()
            ->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->where(function ($q) use ($roomId) {
                $q->whereHas('item', fn ($iq) => $iq->where('room_id', $roomId))
                    ->orWhereHas('asset', fn ($aq) => $aq->where('room_id', $roomId));
            })
            ->count();
    }

    public static function openMaintenanceForRoom(int $roomId): int
    {
        return InventoryMaintenance::query()
            ->whereIn('status', ['Terjadwal', 'Dalam Proses'])
            ->where(function ($q) use ($roomId) {
                $q->whereHas('item', fn ($iq) => $iq->where('room_id', $roomId))
                    ->orWhereHas('asset', fn ($aq) => $aq->where('room_id', $roomId));
            })
            ->count();
    }

    /** @param list<int> $roomIds */
    public static function inventoryCountsByRoom(array $roomIds): Collection
    {
        if ($roomIds === []) {
            return collect();
        }

        $stock = InventoryItem::whereIn('room_id', $roomIds)
            ->where('tracking_type', InventoryCatalog::TRACKING_STOCK)
            ->selectRaw('room_id, COUNT(*) as cnt')
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        $assets = InventoryAsset::whereIn('room_id', $roomIds)
            ->active()
            ->selectRaw('room_id, COUNT(*) as cnt')
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        $result = collect();
        foreach ($roomIds as $roomId) {
            $result[$roomId] = (int) ($stock[$roomId] ?? 0) + (int) ($assets[$roomId] ?? 0);
        }

        return $result;
    }

    /** @param list<int> $roomIds */
    public static function damagedCountsByRoom(array $roomIds): Collection
    {
        if ($roomIds === []) {
            return collect();
        }

        $stockDamaged = InventoryItem::whereIn('room_id', $roomIds)
            ->where('tracking_type', InventoryCatalog::TRACKING_STOCK)
            ->where(function ($q) {
                $q->whereIn('condition', ['Rusak Ringan', 'Rusak Sedang', 'Rusak Berat'])
                    ->orWhereIn('status', ['Rusak', 'Hilang']);
            })
            ->selectRaw('room_id, COUNT(*) as cnt')
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        $assetDamaged = InventoryAsset::whereIn('room_id', $roomIds)
            ->active()
            ->where(function ($q) {
                $q->whereIn('condition', ['Rusak Ringan', 'Rusak Berat', 'Habis Pakai'])
                    ->orWhereIn('status', ['Rusak', 'Hilang']);
            })
            ->selectRaw('room_id, COUNT(*) as cnt')
            ->groupBy('room_id')
            ->pluck('cnt', 'room_id');

        $result = collect();
        foreach ($roomIds as $roomId) {
            $result[$roomId] = (int) ($stockDamaged[$roomId] ?? 0) + (int) ($assetDamaged[$roomId] ?? 0);
        }

        return $result;
    }
}
