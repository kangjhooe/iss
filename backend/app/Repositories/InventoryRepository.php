<?php

namespace App\Repositories;

use App\Models\InventoryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InventoryRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return InventoryItem::class;
    }

    /**
     * Get list of inventory items with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        if (!empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        } elseif (!empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        // Filter by institution if provided
        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Apply filters
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['condition'])) {
            $query->where('condition', $filters['condition']);
        }

        if (isset($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }

        if (!empty($filters['room_ids']) && is_array($filters['room_ids'])) {
            $query->whereIn('room_id', $filters['room_ids']);
        }

        if (isset($filters['building_id'])) {
            $query->where('building_id', $filters['building_id']);
        }

        if (isset($filters['tracking_type'])) {
            $query->where('tracking_type', $filters['tracking_type']);
        }

        if (!empty($filters['disposed'])) {
            $query->whereNotNull('disposed_at');
        } else {
            $query->whereNull('disposed_at');
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                  ->orWhere('name', 'like', '%' . $search . '%')
                  ->orWhere('brand', 'like', '%' . $search . '%')
                  ->orWhere('model', 'like', '%' . $search . '%')
                  ->orWhere('serial_number', 'like', '%' . $search . '%');
            });
        }

        $perPage = min($perPage, 100);

        return $query->with(['category', 'room', 'building', 'institution', 'creator', 'responsibleEmployee'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Export list without pagination (max 5000).
     */
    public function listForExport(array $filters, ?int $institutionId = null, int $limit = 5000)
    {
        $query = $this->query();

        if (! empty($filters['only_trashed'])) {
            $query->onlyTrashed();
        } elseif (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $query->where('category_id', $filters['category_id']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['condition']) && $filters['condition'] !== '') {
            $query->where('condition', $filters['condition']);
        }
        if (isset($filters['room_id']) && $filters['room_id'] !== '') {
            $query->where('room_id', $filters['room_id']);
        }
        if (! empty($filters['room_ids']) && is_array($filters['room_ids'])) {
            $query->whereIn('room_id', $filters['room_ids']);
        }
        if (isset($filters['building_id']) && $filters['building_id'] !== '') {
            $query->where('building_id', $filters['building_id']);
        }
        if (isset($filters['tracking_type']) && $filters['tracking_type'] !== '') {
            $query->where('tracking_type', $filters['tracking_type']);
        }
        if (! empty($filters['disposed'])) {
            $query->whereNotNull('disposed_at');
        } else {
            $query->whereNull('disposed_at');
        }
        if (isset($filters['search']) && ! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('brand', 'like', '%' . $search . '%')
                    ->orWhere('model', 'like', '%' . $search . '%')
                    ->orWhere('serial_number', 'like', '%' . $search . '%');
            });
        }

        return $query->with(['category', 'room', 'building'])
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get inventory item with relationships.
     */
    public function findWithRelations(int $id): InventoryItem
    {
        return $this->query()
            ->with([
                'institution',
                'category',
                'room',
                'building',
                'responsibleEmployee',
                'transactions.creator',
                'maintenances',
                'loans',
                'creator',
                'updater'
            ])
            ->findOrFail($id);
    }
}
