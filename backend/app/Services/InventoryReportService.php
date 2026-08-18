<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryMaintenance;
use App\Models\InventoryLoan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    public const REPORT_TYPES = [
        'summary',
        'stock',
        'asset',
        'damaged',
        'loaned',
        'transactions',
        'maintenance',
    ];

    public const REPORT_TYPE_LABELS = [
        'summary' => 'Ringkasan Inventaris',
        'stock' => 'Daftar Stok Barang',
        'asset' => 'Nilai Aset',
        'damaged' => 'Barang Rusak / Hilang',
        'loaned' => 'Peminjaman Aktif',
        'transactions' => 'Mutasi / Transaksi',
        'maintenance' => 'Pemeliharaan',
    ];

    /**
     * Normalize report filters from request input.
     */
    public function normalizeFilters(array $input): array
    {
        return [
            'category_id' => !empty($input['category_id']) ? (int) $input['category_id'] : null,
            'status' => !empty($input['status']) ? (string) $input['status'] : null,
            'condition' => !empty($input['condition']) ? (string) $input['condition'] : null,
            'building_id' => !empty($input['building_id']) ? (int) $input['building_id'] : null,
            'room_id' => !empty($input['room_id']) ? (int) $input['room_id'] : null,
            'date_from' => !empty($input['date_from']) ? (string) $input['date_from'] : null,
            'date_to' => !empty($input['date_to']) ? (string) $input['date_to'] : null,
            'year' => !empty($input['year']) ? (string) $input['year'] : null,
            'transaction_type' => !empty($input['transaction_type']) ? (string) $input['transaction_type'] : null,
        ];
    }

    public function resolveReportType(?string $type): string
    {
        $type = $type ?: 'summary';

        return in_array($type, self::REPORT_TYPES, true) ? $type : 'summary';
    }

    public function reportTypeLabel(string $type): string
    {
        return self::REPORT_TYPE_LABELS[$type] ?? self::REPORT_TYPE_LABELS['summary'];
    }

    /**
     * Human-readable filter legend for UI/PDF header.
     */
    public function buildFilterLegend(array $filters): array
    {
        $legend = [];

        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? '…';
            $to = $filters['date_to'] ?? '…';
            $legend[] = "Periode: {$from} s/d {$to}";
        }
        if (!empty($filters['year'])) {
            $legend[] = 'Tahun: ' . $filters['year'];
        }
        if (!empty($filters['category_id'])) {
            $name = DB::table('inventory_category')->where('id', $filters['category_id'])->value('name');
            $legend[] = 'Kategori: ' . ($name ?: '#' . $filters['category_id']);
        }
        if (!empty($filters['status'])) {
            $legend[] = 'Status: ' . $filters['status'];
        }
        if (!empty($filters['condition'])) {
            $legend[] = 'Kondisi: ' . $filters['condition'];
        }
        if (!empty($filters['building_id'])) {
            $name = DB::table('building')->where('id', $filters['building_id'])->value('name');
            $legend[] = 'Gedung: ' . ($name ?: '#' . $filters['building_id']);
        }
        if (!empty($filters['room_id'])) {
            $name = DB::table('room')->where('id', $filters['room_id'])->value('name');
            $legend[] = 'Ruangan: ' . ($name ?: '#' . $filters['room_id']);
        }
        if (!empty($filters['transaction_type'])) {
            $legend[] = 'Jenis transaksi: ' . $filters['transaction_type'];
        }

        return $legend;
    }

    protected function applyItemFilters(Builder $query, array $filters, string $table = 'inventory_item'): Builder
    {
        if (!empty($filters['category_id'])) {
            $query->where("{$table}.category_id", $filters['category_id']);
        }
        if (!empty($filters['status'])) {
            $query->where("{$table}.status", $filters['status']);
        }
        if (!empty($filters['condition'])) {
            $query->where("{$table}.condition", $filters['condition']);
        }
        if (!empty($filters['building_id'])) {
            $query->where("{$table}.building_id", $filters['building_id']);
        }
        if (!empty($filters['room_id'])) {
            $query->where("{$table}.room_id", $filters['room_id']);
        }

        return $query;
    }

    protected function itemLocationLabel(InventoryItem $item): string
    {
        if ($item->room) {
            return $item->room->name;
        }
        if ($item->building) {
            return $item->building->name;
        }

        return $item->location_note ?: '-';
    }

    protected function mapStockItem(InventoryItem $item): array
    {
        $unitPrice = $item->purchase_price !== null ? (float) $item->purchase_price : null;
        $qty = (int) ($item->quantity ?? 0);

        return [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'brand' => $item->brand,
            'model' => $item->model,
            'serial_number' => $item->serial_number,
            'brand_model' => trim(implode(' / ', array_filter([$item->brand, $item->model]))) ?: '-',
            'category' => $item->category?->name ?? '-',
            'quantity' => $qty,
            'unit' => $item->unit,
            'condition' => $item->condition,
            'status' => $item->status,
            'location' => $this->itemLocationLabel($item),
            'purchase_date' => $item->purchase_date?->format('Y-m-d'),
            'purchase_price' => $unitPrice,
            'total_value' => $unitPrice !== null ? $unitPrice * $qty : null,
            'supplier' => $item->supplier,
            'warranty_expiry' => $item->warranty_expiry?->format('Y-m-d'),
        ];
    }

    /**
     * Get inventory statistics.
     */
    public function getStatistics(?int $institutionId = null, ?string $year = null, array $filters = []): array
    {
        $query = InventoryItem::query();

        if ($institutionId) {
            $query->where('inventory_item.institution_id', $institutionId);
        }

        $this->applyItemFilters($query, $filters);

        if ($year) {
            $query->whereYear('inventory_item.purchase_date', $year);
        }

        $totalByStatus = (clone $query)
            ->select('inventory_item.status', DB::raw('count(*) as total'), DB::raw('sum(inventory_item.quantity) as total_quantity'))
            ->groupBy('inventory_item.status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => [
                'count' => $item->total,
                'quantity' => $item->total_quantity,
            ]])
            ->toArray();

        $totalByCondition = (clone $query)
            ->select('inventory_item.condition', DB::raw('count(*) as total'), DB::raw('sum(inventory_item.quantity) as total_quantity'))
            ->groupBy('inventory_item.condition')
            ->get()
            ->mapWithKeys(fn($item) => [$item->condition => [
                'count' => $item->total,
                'quantity' => $item->total_quantity,
            ]])
            ->toArray();

        $totalByCategory = (clone $query)
            ->join('inventory_category', 'inventory_item.category_id', '=', 'inventory_category.id')
            ->select('inventory_category.name', DB::raw('count(*) as total'), DB::raw('sum(inventory_item.quantity) as total_quantity'))
            ->groupBy('inventory_category.name')
            ->get()
            ->mapWithKeys(fn($item) => [$item->name => [
                'count' => $item->total,
                'quantity' => $item->total_quantity,
            ]])
            ->toArray();

        $totalValue = (clone $query)
            ->whereNotNull('inventory_item.purchase_price')
            ->selectRaw('sum(inventory_item.purchase_price * inventory_item.quantity) as total')
            ->value('total') ?? 0;

        $warrantyExpiring = (clone $query)
            ->whereNotNull('inventory_item.warranty_expiry')
            ->whereBetween('inventory_item.warranty_expiry', [now(), now()->addMonths(3)])
            ->count();

        return [
            'total_items' => (clone $query)->count(),
            'total_quantity' => (clone $query)->sum('inventory_item.quantity'),
            'total_value' => $totalValue,
            'by_status' => $totalByStatus,
            'by_condition' => $totalByCondition,
            'by_category' => $totalByCategory,
            'warranty_expiring_soon' => $warrantyExpiring,
        ];
    }

    /**
     * Flat stock / daftar barang report.
     */
    public function getStockReport(?int $institutionId = null, array $filters = [], int $limit = 5000): array
    {
        $query = InventoryItem::with(['category:id,name', 'room:id,name', 'building:id,name']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $this->applyItemFilters($query, $filters);
        $query->orderBy('name');

        $total = (clone $query)->count();
        $items = $query->limit($limit)->get()->map(fn($item) => $this->mapStockItem($item))->values();

        return [
            'items' => $items->toArray(),
            'total' => $total,
            'truncated' => $total > $items->count(),
            'total_quantity' => $items->sum('quantity'),
            'total_value' => $items->sum(fn($row) => $row['total_value'] ?? 0),
        ];
    }

    /**
     * Get items by category.
     */
    public function getItemsByCategory(?int $institutionId = null, ?int $categoryId = null, array $filters = []): array
    {
        $query = InventoryItem::with(['category', 'room', 'building']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $merged = $filters;
        if ($categoryId) {
            $merged['category_id'] = $categoryId;
        }
        $this->applyItemFilters($query, $merged);

        return $query->get()
            ->groupBy(fn($item) => $item->category?->name ?? 'Tanpa Kategori')
            ->map(function ($items, $categoryName) {
                return [
                    'category' => $categoryName,
                    'count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'items' => $items->map(fn($item) => $this->mapStockItem($item))->values(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get items by location.
     */
    public function getItemsByLocation(?int $institutionId = null, array $filters = []): array
    {
        $query = InventoryItem::with(['room', 'building', 'category']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $this->applyItemFilters($query, $filters);
        $items = $query->get();

        $byRoom = $items->whereNotNull('room_id')
            ->groupBy(fn($item) => $item->room?->name ?? '-')
            ->map(function ($grouped, $roomName) {
                return [
                    'location_type' => 'room',
                    'location_name' => $roomName,
                    'count' => $grouped->count(),
                    'total_quantity' => $grouped->sum('quantity'),
                    'items' => $grouped->map(fn($item) => [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'category' => $item->category?->name ?? '-',
                    ])->values(),
                ];
            })
            ->values();

        $byBuilding = $items->whereNotNull('building_id')
            ->whereNull('room_id')
            ->groupBy(fn($item) => $item->building?->name ?? '-')
            ->map(function ($grouped, $buildingName) {
                return [
                    'location_type' => 'building',
                    'location_name' => $buildingName,
                    'count' => $grouped->count(),
                    'total_quantity' => $grouped->sum('quantity'),
                    'items' => $grouped->map(fn($item) => [
                        'id' => $item->id,
                        'code' => $item->code,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'category' => $item->category?->name ?? '-',
                    ])->values(),
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
    public function getDamagedMissingItems(?int $institutionId = null, array $filters = []): array
    {
        $query = InventoryItem::with(['category', 'room', 'building']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $this->applyItemFilters($query, array_diff_key($filters, array_flip(['status', 'condition'])));

        $damaged = (clone $query)
            ->whereIn('condition', ['Rusak Ringan', 'Rusak Berat'])
            ->orderBy('name')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'category' => $item->category?->name ?? '-',
                'condition' => $item->condition,
                'status' => $item->status,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'location' => $this->itemLocationLabel($item),
                'label' => $item->condition ?? 'Rusak',
            ]);

        $missing = (clone $query)
            ->where('status', 'Hilang')
            ->orderBy('name')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'category' => $item->category?->name ?? '-',
                'condition' => $item->condition,
                'status' => $item->status,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'location' => $this->itemLocationLabel($item),
                'label' => 'Hilang',
            ]);

        return [
            'damaged' => $damaged->toArray(),
            'missing' => $missing->toArray(),
            'rows' => $damaged->concat($missing)->values()->toArray(),
            'total_damaged' => $damaged->count(),
            'total_missing' => $missing->count(),
        ];
    }

    /**
     * Get loaned items.
     */
    public function getLoanedItems(?int $institutionId = null, array $filters = []): array
    {
        $query = InventoryLoan::with(['item.category', 'borrowerEmployee', 'borrowerStudent']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('loan_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('loan_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['category_id'])) {
            $query->whereHas('item', fn($q) => $q->where('category_id', $filters['category_id']));
        }

        $loans = $query->whereIn('status', ['Dipinjam', 'Terlambat'])
            ->orderBy('expected_return_date')
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
                    'item_code' => $loan->item?->code ?? '-',
                    'item_name' => $loan->item?->name ?? '-',
                    'category' => $loan->item?->category?->name ?? '-',
                    'borrower_type' => $loan->borrower_type,
                    'borrower_name' => $borrowerName,
                    'borrower_phone' => $loan->borrower_phone,
                    'quantity' => $loan->quantity,
                    'loan_date' => $loan->loan_date?->format('Y-m-d'),
                    'expected_return_date' => $loan->expected_return_date?->format('Y-m-d'),
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
    public function getAssetValueReport(?int $institutionId = null, array $filters = []): array
    {
        $query = InventoryItem::with(['category']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $this->applyItemFilters($query, $filters);
        $items = $query->whereNotNull('purchase_price')->orderBy('name')->get();

        $byCategory = $items->groupBy(fn($item) => $item->category?->name ?? 'Tanpa Kategori')
            ->map(function ($grouped, $categoryName) {
                $totalValue = $grouped->sum(fn($item) => (float) $item->purchase_price * (int) $item->quantity);

                return [
                    'category' => $categoryName,
                    'item_count' => $grouped->count(),
                    'total_quantity' => $grouped->sum('quantity'),
                    'total_value' => $totalValue,
                    'items' => $grouped->map(fn($item) => [
                        'code' => $item->code,
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'unit_price' => (float) $item->purchase_price,
                        'total_value' => (float) $item->purchase_price * (int) $item->quantity,
                        'purchase_date' => $item->purchase_date?->format('Y-m-d'),
                    ])->values(),
                ];
            })
            ->values();

        return [
            'by_category' => $byCategory->toArray(),
            'grand_total_value' => $items->sum(fn($item) => (float) $item->purchase_price * (int) $item->quantity),
            'total_items' => $items->count(),
            'total_quantity' => $items->sum('quantity'),
        ];
    }

    /**
     * Get maintenance report.
     */
    public function getMaintenanceReport(?int $institutionId = null, ?string $year = null, array $filters = []): array
    {
        $query = InventoryMaintenance::with(['item.category']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $year = $year ?: ($filters['year'] ?? null);
        if ($year) {
            $query->whereYear('scheduled_date', $year);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('scheduled_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('scheduled_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['category_id'])) {
            $query->whereHas('item', fn($q) => $q->where('category_id', $filters['category_id']));
        }

        $maintenances = $query->orderByDesc('scheduled_date')->get();

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
                'item_code' => $m->item?->code ?? '-',
                'item_name' => $m->item?->name ?? '-',
                'category' => $m->item?->category?->name ?? '-',
                'type' => $m->maintenance_type,
                'scheduled_date' => $m->scheduled_date?->format('Y-m-d'),
                'completed_date' => $m->completed_date?->format('Y-m-d'),
                'cost' => $m->cost,
                'status' => $m->status,
                'technician_name' => $m->technician_name,
                'notes' => $m->notes,
            ])->toArray(),
        ];
    }

    /**
     * Get transaction report.
     */
    public function getTransactionReport(?int $institutionId = null, ?string $dateFrom = null, ?string $dateTo = null, array $filters = []): array
    {
        $query = InventoryTransaction::with(['item.category', 'creator', 'fromLocation:id,name', 'toLocation:id,name']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $dateFrom = $dateFrom ?: ($filters['date_from'] ?? null);
        $dateTo = $dateTo ?: ($filters['date_to'] ?? null);

        if ($dateFrom) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }
        if (!empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }
        if (!empty($filters['category_id'])) {
            $query->whereHas('item', fn($q) => $q->where('category_id', $filters['category_id']));
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->limit(5000)->get();

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
                'item_code' => $t->item?->code ?? '-',
                'item_name' => $t->item?->name ?? '-',
                'category' => $t->item?->category?->name ?? '-',
                'type' => $t->transaction_type,
                'date' => $t->transaction_date?->format('Y-m-d'),
                'quantity' => $t->quantity,
                'reference_number' => $t->reference_number,
                'from_location' => $t->fromLocation?->name,
                'to_location' => $t->toLocation?->name,
                'notes' => $t->notes,
                'created_by' => $t->creator?->name ?? '-',
            ])->toArray(),
        ];
    }
}
