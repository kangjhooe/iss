<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryMaintenance;
use App\Models\InventoryLoan;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    /**
     * Get inventory statistics.
     */
    public function getStatistics(?int $institutionId = null, ?string $year = null): array
    {
        $query = InventoryItem::query();

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        // Total by status
        $totalByStatus = (clone $query)
            ->select('status', DB::raw('count(*) as total'), DB::raw('sum(quantity) as total_quantity'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => [
                'count' => $item->total,
                'quantity' => $item->total_quantity
            ]])
            ->toArray();

        // Total by condition
        $totalByCondition = (clone $query)
            ->select('condition', DB::raw('count(*) as total'), DB::raw('sum(quantity) as total_quantity'))
            ->groupBy('condition')
            ->get()
            ->mapWithKeys(fn($item) => [$item->condition => [
                'count' => $item->total,
                'quantity' => $item->total_quantity
            ]])
            ->toArray();

        // Total by category
        $totalByCategory = (clone $query)
            ->join('inventory_category', 'inventory_item.category_id', '=', 'inventory_category.id')
            ->select('inventory_category.name', DB::raw('count(*) as total'), DB::raw('sum(inventory_item.quantity) as total_quantity'))
            ->groupBy('inventory_category.name')
            ->get()
            ->mapWithKeys(fn($item) => [$item->name => [
                'count' => $item->total,
                'quantity' => $item->total_quantity
            ]])
            ->toArray();

        // Total value
        $totalValue = (clone $query)
            ->whereNotNull('purchase_price')
            ->selectRaw('sum(purchase_price * quantity) as total')
            ->value('total') ?? 0;

        // Items with warranty expiring soon (next 3 months)
        $warrantyExpiring = (clone $query)
            ->whereNotNull('warranty_expiry')
            ->whereBetween('warranty_expiry', [now(), now()->addMonths(3)])
            ->count();

        return [
            'total_items' => (clone $query)->count(),
            'total_quantity' => (clone $query)->sum('quantity'),
            'total_value' => $totalValue,
            'by_status' => $totalByStatus,
            'by_condition' => $totalByCondition,
            'by_category' => $totalByCategory,
            'warranty_expiring_soon' => $warrantyExpiring,
        ];
    }

    /**
     * Get items by category.
     */
    public function getItemsByCategory(?int $institutionId = null, ?int $categoryId = null): array
    {
        $query = InventoryItem::with(['category', 'room', 'building']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        return $query->get()
            ->groupBy('category.name')
            ->map(function ($items, $categoryName) {
                return [
                    'category' => $categoryName,
                    'count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'items' => $items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'code' => $item->code,
                            'name' => $item->name,
                            'quantity' => $item->quantity,
                            'condition' => $item->condition,
                            'status' => $item->status,
                            'location' => $item->room ? $item->room->name : ($item->building ? $item->building->name : '-'),
                        ];
                    }),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get items by location.
     */
    public function getItemsByLocation(?int $institutionId = null): array
    {
        $query = InventoryItem::with(['room', 'building', 'category']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $items = $query->get();

        $byRoom = $items->whereNotNull('room_id')
            ->groupBy('room.name')
            ->map(function ($items, $roomName) {
                return [
                    'location_type' => 'room',
                    'location_name' => $roomName,
                    'count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'items' => $items->map(fn($item) => [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'category' => $item->category->name,
                    ]),
                ];
            })
            ->values();

        $byBuilding = $items->whereNotNull('building_id')
            ->whereNull('room_id')
            ->groupBy('building.name')
            ->map(function ($items, $buildingName) {
                return [
                    'location_type' => 'building',
                    'location_name' => $buildingName,
                    'count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'items' => $items->map(fn($item) => [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'category' => $item->category->name,
                    ]),
                ];
            })
            ->values();

        return [
            'by_room' => $byRoom->toArray(),
            'by_building' => $byBuilding->toArray(),
        ];
    }

    /**
     * Get damaged/missing items.
     */
    public function getDamagedMissingItems(?int $institutionId = null): array
    {
        $query = InventoryItem::with(['category', 'room', 'building']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $damaged = (clone $query)
            ->whereIn('condition', ['Rusak Ringan', 'Rusak Berat'])
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'category' => $item->category->name,
                'condition' => $item->condition,
                'quantity' => $item->quantity,
                'location' => $item->room ? $item->room->name : ($item->building ? $item->building->name : '-'),
            ]);

        $missing = (clone $query)
            ->where('status', 'Hilang')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'category' => $item->category->name,
                'quantity' => $item->quantity,
                'location' => $item->room ? $item->room->name : ($item->building ? $item->building->name : '-'),
            ]);

        return [
            'damaged' => $damaged->toArray(),
            'missing' => $missing->toArray(),
            'total_damaged' => $damaged->count(),
            'total_missing' => $missing->count(),
        ];
    }

    /**
     * Get loaned items.
     */
    public function getLoanedItems(?int $institutionId = null): array
    {
        $query = InventoryLoan::with(['item.category', 'borrowerEmployee', 'borrowerStudent']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $loans = $query->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->get()
            ->map(function ($loan) {
                $borrowerName = $loan->borrower_name;
                if ($loan->borrower_type === 'Employee' && $loan->borrowerEmployee) {
                    $borrowerName = $loan->borrowerEmployee->name;
                } elseif ($loan->borrower_type === 'Student' && $loan->borrowerStudent) {
                    $borrowerName = $loan->borrowerStudent->name;
                }

                return [
                    'id' => $loan->id,
                    'item_code' => $loan->item->code,
                    'item_name' => $loan->item->name,
                    'category' => $loan->item->category->name,
                    'borrower_type' => $loan->borrower_type,
                    'borrower_name' => $borrowerName,
                    'borrower_phone' => $loan->borrower_phone,
                    'quantity' => $loan->quantity,
                    'loan_date' => $loan->loan_date->format('Y-m-d'),
                    'expected_return_date' => $loan->expected_return_date->format('Y-m-d'),
                    'status' => $loan->status,
                    'is_overdue' => $loan->isOverdue(),
                ];
            });

        return [
            'loans' => $loans->toArray(),
            'total' => $loans->count(),
            'overdue' => $loans->where('is_overdue', true)->count(),
        ];
    }

    /**
     * Get asset value report.
     */
    public function getAssetValueReport(?int $institutionId = null): array
    {
        $query = InventoryItem::with(['category']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $items = $query->whereNotNull('purchase_price')->get();

        $byCategory = $items->groupBy('category.name')
            ->map(function ($items, $categoryName) {
                $totalValue = $items->sum(fn($item) => $item->purchase_price * $item->quantity);
                return [
                    'category' => $categoryName,
                    'item_count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'total_value' => $totalValue,
                    'items' => $items->map(fn($item) => [
                        'code' => $item->code,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->purchase_price,
                        'total_value' => $item->purchase_price * $item->quantity,
                    ]),
                ];
            })
            ->values();

        return [
            'by_category' => $byCategory->toArray(),
            'grand_total_value' => $items->sum(fn($item) => $item->purchase_price * $item->quantity),
            'total_items' => $items->count(),
        ];
    }

    /**
     * Get maintenance report.
     */
    public function getMaintenanceReport(?int $institutionId = null, ?string $year = null): array
    {
        $query = InventoryMaintenance::with(['item.category']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if ($year) {
            $query->whereYear('scheduled_date', $year);
        }

        $maintenances = $query->get();

        $byType = $maintenances->groupBy('maintenance_type')
            ->map(function ($items, $type) {
                return [
                    'type' => $type,
                    'count' => $items->count(),
                    'total_cost' => $items->sum('cost'),
                    'completed' => $items->where('status', 'Selesai')->count(),
                    'pending' => $items->whereIn('status', ['Terjadwal', 'Dalam Proses'])->count(),
                ];
            })
            ->values();

        $byStatus = $maintenances->groupBy('status')
            ->map(function ($items, $status) {
                return [
                    'status' => $status,
                    'count' => $items->count(),
                ];
            })
            ->values();

        return [
            'by_type' => $byType->toArray(),
            'by_status' => $byStatus->toArray(),
            'total' => $maintenances->count(),
            'total_cost' => $maintenances->sum('cost'),
            'maintenances' => $maintenances->map(fn($m) => [
                'id' => $m->id,
                'item_code' => $m->item->code,
                'item_name' => $m->item->name,
                'category' => $m->item->category->name,
                'type' => $m->maintenance_type,
                'scheduled_date' => $m->scheduled_date->format('Y-m-d'),
                'completed_date' => $m->completed_date?->format('Y-m-d'),
                'cost' => $m->cost,
                'status' => $m->status,
            ])->toArray(),
        ];
    }

    /**
     * Get transaction report.
     */
    public function getTransactionReport(?int $institutionId = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = InventoryTransaction::with(['item.category', 'creator']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if ($dateFrom) {
            $query->where('transaction_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('transaction_date', '<=', $dateTo);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        $byType = $transactions->groupBy('transaction_type')
            ->map(function ($items, $type) {
                return [
                    'type' => $type,
                    'count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                ];
            })
            ->values();

        return [
            'by_type' => $byType->toArray(),
            'total_transactions' => $transactions->count(),
            'transactions' => $transactions->map(fn($t) => [
                'id' => $t->id,
                'item_code' => $t->item->code,
                'item_name' => $t->item->name,
                'category' => $t->item->category->name,
                'type' => $t->transaction_type,
                'date' => $t->transaction_date->format('Y-m-d'),
                'quantity' => $t->quantity,
                'reference_number' => $t->reference_number,
                'created_by' => $t->creator->name,
            ])->toArray(),
        ];
    }
}
