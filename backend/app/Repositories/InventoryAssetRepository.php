<?php

namespace App\Repositories;

use App\Models\InventoryAsset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryAssetRepository extends BaseRepository
{
    protected function model(): string
    {
        return InventoryAsset::class;
    }

    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query()->with([
            'item.category',
            'room',
            'building',
            'responsibleEmployee',
        ]);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if (! empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        if (! empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (! empty($filters['room_ids']) && is_array($filters['room_ids'])) {
            $query->whereIn('room_id', $filters['room_ids']);
        }

        if (! empty($filters['building_id'])) {
            $query->where('building_id', $filters['building_id']);
        }

        if (! empty($filters['disposal_status'])) {
            $query->where('disposal_status', $filters['disposal_status']);
        } elseif (empty($filters['include_disposed'])) {
            $query->active();
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('asset_number', 'like', "%{$search}%")
                    ->orWhere('inventory_number', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhereHas('item', function ($iq) use ($search) {
                        $iq->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min($perPage, 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findWithRelations(int $id): InventoryAsset
    {
        return $this->query()
            ->with([
                'item.category',
                'room',
                'building',
                'responsibleEmployee',
                'creator',
                'updater',
                'loans',
                'maintenances',
            ])
            ->findOrFail($id);
    }
}
