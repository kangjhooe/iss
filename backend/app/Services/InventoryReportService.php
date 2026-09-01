<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryAsset;
use App\Models\InventoryAssetMovement;
use App\Models\InventoryTransaction;
use App\Models\InventoryMaintenance;
use App\Models\InventoryLoan;
use App\Support\InventoryCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class InventoryReportService
{
    public const REPORT_TYPES = [
        'summary',
        'stock',
        'location',
        'category',
        'asset',
        'damaged',
        'loaned',
        'transactions',
        'maintenance',
        'disposal',
        'asset_movements',
    ];

    public const REPORT_TYPE_LABELS = [
        'summary' => 'Ringkasan Inventaris',
        'stock' => 'Daftar Stok Barang',
        'location' => 'Inventaris per Ruangan',
        'category' => 'Inventaris per Kategori',
        'asset' => 'Estimasi Nilai Perolehan',
        'damaged' => 'Barang Rusak / Hilang',
        'loaned' => 'Peminjaman Aktif',
        'transactions' => 'Mutasi / Transaksi',
        'maintenance' => 'Pemeliharaan',
        'disposal' => 'Penghapusan Barang',
        'asset_movements' => 'Mutasi Aset Individual',
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
        $query->whereNull("{$table}.disposed_at");

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
        if (! empty($filters['room_ids']) && is_array($filters['room_ids'])) {
            $query->whereIn("{$table}.room_id", $filters['room_ids']);
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

    protected function assetLocationLabel(InventoryAsset $asset): string
    {
        if ($asset->room) {
            return $asset->room->name;
        }
        if ($asset->building) {
            return $asset->building->name;
        }

        return $asset->location_note ?: '-';
    }

    protected function activeAssetQuery(?int $institutionId, array $filters): Builder
    {
        $query = InventoryAsset::query()
            ->where('inventory_asset.disposal_status', InventoryCatalog::DISPOSAL_ACTIVE)
            ->whereNull('inventory_asset.disposed_at');

        if ($institutionId) {
            $query->where('inventory_asset.institution_id', $institutionId);
        }
        if (! empty($filters['building_id'])) {
            $query->where('inventory_asset.building_id', $filters['building_id']);
        }
        if (! empty($filters['room_id'])) {
            $query->where('inventory_asset.room_id', $filters['room_id']);
        }
        if (! empty($filters['room_ids']) && is_array($filters['room_ids'])) {
            $query->whereIn('inventory_asset.room_id', $filters['room_ids']);
        }
        if (! empty($filters['status'])) {
            $query->where('inventory_asset.status', $filters['status']);
        }
        if (! empty($filters['condition'])) {
            $query->where('inventory_asset.condition', $filters['condition']);
        }
        if (! empty($filters['category_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        return $query;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array<string, array{count:int, quantity:int}>
     */
    protected function statRowsToMap($rows): array
    {
        return $rows->mapWithKeys(fn ($item) => [$item->status ?? $item->condition ?? $item->name => [
            'count' => (int) ($item->total ?? 0),
            'quantity' => (int) ($item->total_quantity ?? 0),
        ]])->toArray();
    }

    /**
     * @param  array<string, array{count:int, quantity:int}>  ...$buckets
     * @return array<string, array{count:int, quantity:int}>
     */
    protected function mergeStatBuckets(array ...$buckets): array
    {
        $merged = [];
        foreach ($buckets as $bucket) {
            foreach ($bucket as $key => $row) {
                if (! isset($merged[$key])) {
                    $merged[$key] = ['count' => 0, 'quantity' => 0];
                }
                $merged[$key]['count'] += (int) ($row['count'] ?? 0);
                $merged[$key]['quantity'] += (int) ($row['quantity'] ?? 0);
            }
        }

        return $merged;
    }

    protected function assetUnitPrice(InventoryAsset $asset): ?float
    {
        if ($asset->purchase_price !== null) {
            return (float) $asset->purchase_price;
        }
        if ($asset->item?->purchase_price !== null) {
            return (float) $asset->item->purchase_price;
        }

        return null;
    }

    protected function mapStockItem(InventoryItem $item): array
    {
        $unitPrice = $item->purchase_price !== null ? (float) $item->purchase_price : null;
        $qty = (int) ($item->quantity ?? 0);

        return [
            'id' => $item->id,
            'code' => $item->code,
            'name' => $item->name,
            'tracking_type' => $item->tracking_type ?? InventoryCatalog::TRACKING_STOCK,
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
        $itemQuery = InventoryItem::query();

        if ($institutionId) {
            $itemQuery->where('inventory_item.institution_id', $institutionId);
        }

        $this->applyItemFilters($itemQuery, $filters);

        if ($year) {
            $itemQuery->whereYear('inventory_item.purchase_date', $year);
        }

        $stockQuery = (clone $itemQuery)->where('inventory_item.tracking_type', InventoryCatalog::TRACKING_STOCK);

        $stockByStatus = $this->statRowsToMap(
            (clone $stockQuery)
                ->select('inventory_item.status', DB::raw('count(*) as total'), DB::raw('sum(inventory_item.quantity) as total_quantity'))
                ->groupBy('inventory_item.status')
                ->get()
        );

        $stockByCondition = $this->statRowsToMap(
            (clone $stockQuery)
                ->select('inventory_item.condition', DB::raw('count(*) as total'), DB::raw('sum(inventory_item.quantity) as total_quantity'))
                ->groupBy('inventory_item.condition')
                ->get()
        );

        $assetQuery = $this->activeAssetQuery($institutionId, $filters);
        if ($year) {
            $assetQuery->where(function ($q) use ($year) {
                $q->whereYear('inventory_asset.purchase_date', $year)
                    ->orWhereHas('item', fn ($iq) => $iq->whereYear('purchase_date', $year));
            });
        }

        $assetByStatus = $this->statRowsToMap(
            (clone $assetQuery)
                ->select('inventory_asset.status as status', DB::raw('count(*) as total'), DB::raw('count(*) as total_quantity'))
                ->groupBy('inventory_asset.status')
                ->get()
        );

        $assetByCondition = $this->statRowsToMap(
            (clone $assetQuery)
                ->select('inventory_asset.condition as condition', DB::raw('count(*) as total'), DB::raw('count(*) as total_quantity'))
                ->groupBy('inventory_asset.condition')
                ->get()
        );

        $stockByCategory = (clone $stockQuery)
            ->join('inventory_category', 'inventory_item.category_id', '=', 'inventory_category.id')
            ->select('inventory_category.name', DB::raw('count(*) as total'), DB::raw('sum(inventory_item.quantity) as total_quantity'))
            ->groupBy('inventory_category.name')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->name => [
                'count' => (int) $item->total,
                'quantity' => (int) $item->total_quantity,
            ]])
            ->toArray();

        $assetByCategory = (clone $assetQuery)
            ->join('inventory_item', 'inventory_asset.item_id', '=', 'inventory_item.id')
            ->join('inventory_category', 'inventory_item.category_id', '=', 'inventory_category.id')
            ->select('inventory_category.name', DB::raw('count(*) as total'), DB::raw('count(*) as total_quantity'))
            ->groupBy('inventory_category.name')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->name => [
                'count' => (int) $item->total,
                'quantity' => (int) $item->total_quantity,
            ]])
            ->toArray();

        $stockValue = (clone $stockQuery)
            ->whereNotNull('inventory_item.purchase_price')
            ->selectRaw('sum(inventory_item.purchase_price * inventory_item.quantity) as total')
            ->value('total') ?? 0;

        $assetValue = (clone $assetQuery)
            ->leftJoin('inventory_item as ii', 'inventory_asset.item_id', '=', 'ii.id')
            ->selectRaw('sum(COALESCE(inventory_asset.purchase_price, ii.purchase_price, 0)) as total')
            ->value('total') ?? 0;

        $warrantyExpiring = (clone $itemQuery)
            ->whereNotNull('inventory_item.warranty_expiry')
            ->whereBetween('inventory_item.warranty_expiry', [now(), now()->addMonths(3)])
            ->count();

        return [
            'total_items' => (clone $itemQuery)->count(),
            'total_quantity' => (int) (clone $stockQuery)->sum('inventory_item.quantity') + (clone $assetQuery)->count(),
            'total_value' => (float) $stockValue + (float) $assetValue,
            'individual_asset_count' => (clone $assetQuery)->count(),
            'by_status' => $this->mergeStatBuckets($stockByStatus, $assetByStatus),
            'by_condition' => $this->mergeStatBuckets($stockByCondition, $assetByCondition),
            'by_category' => $this->mergeStatBuckets($stockByCategory, $assetByCategory),
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

        $assetQuery = $this->activeAssetQuery($institutionId, $filters)->with(['room', 'item.category']);
        $assets = $assetQuery->whereNotNull('room_id')->get();

        $roomGroups = [];

        foreach ($items->whereNotNull('room_id') as $item) {
            $roomName = $item->room?->name ?? '-';
            if (! isset($roomGroups[$roomName])) {
                $roomGroups[$roomName] = ['items' => collect(), 'quantity' => 0];
            }
            $roomGroups[$roomName]['items']->push([
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'category' => $item->category?->name ?? '-',
                'tracking_type' => $item->tracking_type ?? InventoryCatalog::TRACKING_STOCK,
                'entity_type' => 'item',
            ]);
            $roomGroups[$roomName]['quantity'] += (int) $item->quantity;
        }

        foreach ($assets as $asset) {
            $roomName = $asset->room?->name ?? '-';
            if (! isset($roomGroups[$roomName])) {
                $roomGroups[$roomName] = ['items' => collect(), 'quantity' => 0];
            }
            $roomGroups[$roomName]['items']->push([
                'id' => $asset->id,
                'code' => $asset->asset_number,
                'name' => ($asset->item?->name ?? '-') . ' (unit)',
                'quantity' => 1,
                'category' => $asset->item?->category?->name ?? '-',
                'tracking_type' => InventoryCatalog::TRACKING_INDIVIDUAL,
                'entity_type' => 'asset',
                'item_code' => $asset->item?->code,
            ]);
            $roomGroups[$roomName]['quantity'] += 1;
        }

        $byRoom = collect($roomGroups)->map(function ($group, $roomName) {
            return [
                'location_type' => 'room',
                'location_name' => $roomName,
                'count' => $group['items']->count(),
                'total_quantity' => $group['quantity'],
                'items' => $group['items']->values(),
            ];
        })->values();

        return [
            'by_room' => $byRoom->toArray(),
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
                'entity_type' => 'item',
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
                'entity_type' => 'item',
            ]);

        $assetFilters = array_diff_key($filters, array_flip(['status', 'condition']));
        $assetQuery = $this->activeAssetQuery($institutionId, $assetFilters)->with(['item.category', 'room', 'building']);

        $assetDamaged = (clone $assetQuery)
            ->whereIn('inventory_asset.condition', ['Rusak Ringan', 'Rusak Berat'])
            ->orderBy('asset_number')
            ->get()
            ->map(fn (InventoryAsset $asset) => [
                'id' => $asset->id,
                'code' => $asset->asset_number,
                'name' => ($asset->item?->name ?? '-') . ' (unit)',
                'category' => $asset->item?->category?->name ?? '-',
                'condition' => $asset->condition,
                'status' => $asset->status,
                'quantity' => 1,
                'unit' => $asset->item?->unit ?? 'Unit',
                'location' => $this->assetLocationLabel($asset),
                'label' => $asset->condition ?? 'Rusak',
                'entity_type' => 'asset',
                'item_code' => $asset->item?->code,
            ]);

        $assetMissing = (clone $assetQuery)
            ->where('inventory_asset.status', 'Hilang')
            ->orderBy('asset_number')
            ->get()
            ->map(fn (InventoryAsset $asset) => [
                'id' => $asset->id,
                'code' => $asset->asset_number,
                'name' => ($asset->item?->name ?? '-') . ' (unit)',
                'category' => $asset->item?->category?->name ?? '-',
                'condition' => $asset->condition,
                'status' => $asset->status,
                'quantity' => 1,
                'unit' => $asset->item?->unit ?? 'Unit',
                'location' => $this->assetLocationLabel($asset),
                'label' => 'Hilang',
                'entity_type' => 'asset',
                'item_code' => $asset->item?->code,
            ]);

        $damaged = $damaged->concat($assetDamaged);
        $missing = $missing->concat($assetMissing);

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
        $query = InventoryLoan::with(['item.category', 'asset', 'borrowerEmployee', 'borrowerStudent']);

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
                    'asset_number' => $loan->asset?->asset_number,
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
        $query = InventoryItem::with(['category', 'assets' => fn ($q) => $q->active()]);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        $this->applyItemFilters($query, $filters);
        $items = $query->orderBy('name')->get();

        $rows = collect();

        foreach ($items as $item) {
            if ($item->tracking_type === InventoryCatalog::TRACKING_INDIVIDUAL) {
                $activeAssets = $item->assets->filter(fn ($a) => $a->disposal_status === InventoryCatalog::DISPOSAL_ACTIVE && ! $a->disposed_at);
                foreach ($activeAssets as $asset) {
                    $unitPrice = $this->assetUnitPrice($asset);
                    if ($unitPrice === null) {
                        continue;
                    }
                    $rows->push([
                        'item' => $item,
                        'asset' => $asset,
                        'code' => $asset->asset_number,
                        'name' => $item->name . ' (unit)',
                        'quantity' => 1,
                        'unit' => $item->unit,
                        'unit_price' => $unitPrice,
                        'total_value' => $unitPrice,
                        'purchase_date' => ($asset->purchase_date ?? $item->purchase_date)?->format('Y-m-d'),
                        'tracking_type' => InventoryCatalog::TRACKING_INDIVIDUAL,
                    ]);
                }
                continue;
            }

            if ($item->purchase_price === null) {
                continue;
            }

            $unitPrice = (float) $item->purchase_price;
            $rows->push([
                'item' => $item,
                'asset' => null,
                'code' => $item->code,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'unit' => $item->unit,
                'unit_price' => $unitPrice,
                'total_value' => $unitPrice * (int) $item->quantity,
                'purchase_date' => $item->purchase_date?->format('Y-m-d'),
                'tracking_type' => InventoryCatalog::TRACKING_STOCK,
            ]);
        }

        $byCategory = $rows->groupBy(fn ($row) => $row['item']->category?->name ?? 'Tanpa Kategori')
            ->map(function ($grouped, $categoryName) {
                return [
                    'category' => $categoryName,
                    'item_count' => $grouped->count(),
                    'total_quantity' => $grouped->sum('quantity'),
                    'total_value' => $grouped->sum('total_value'),
                    'items' => $grouped->map(fn ($row) => [
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'quantity' => $row['quantity'],
                        'unit' => $row['unit'],
                        'unit_price' => $row['unit_price'],
                        'total_value' => $row['total_value'],
                        'purchase_date' => $row['purchase_date'],
                        'tracking_type' => $row['tracking_type'],
                    ])->values(),
                ];
            })
            ->values();

        return [
            'by_category' => $byCategory->toArray(),
            'grand_total_value' => $rows->sum('total_value'),
            'total_items' => $rows->count(),
            'total_quantity' => $rows->sum('quantity'),
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

    /**
     * Get formally disposed items (penghapusan administratif).
     */
    public function getDisposedItems(?int $institutionId = null, array $filters = []): array
    {
        $query = \App\Models\InventoryDisposal::with(['item.category', 'item.room', 'item.building']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
        }
        if (!empty($filters['building_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('building_id', $filters['building_id']));
        }
        if (!empty($filters['room_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('room_id', $filters['room_id']));
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('disposal_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('disposal_date', '<=', $filters['date_to']);
        }

        $records = $query->orderByDesc('disposal_date')->orderByDesc('id')->get();

        return [
            'items' => $records->map(fn ($disposal) => [
                'id' => $disposal->id,
                'item_id' => $disposal->item_id,
                'code' => $disposal->item?->code,
                'name' => $disposal->item?->name,
                'category' => $disposal->item?->category?->name ?? '-',
                'status' => $disposal->status,
                'condition' => $disposal->item?->condition,
                'quantity' => $disposal->quantity,
                'unit' => $disposal->item?->unit,
                'location' => $disposal->item ? $this->itemLocationLabel($disposal->item) : '-',
                'disposed_at' => $disposal->disposal_date?->format('Y-m-d'),
                'disposal_reason' => $disposal->disposal_reason,
                'disposal_document_number' => $disposal->disposal_document_number,
            ])->values()->toArray(),
            'total' => $records->count(),
            'total_quantity' => $records->sum('quantity'),
        ];
    }

    /**
     * Get individual asset movement report.
     */
    public function getAssetMovementReport(?int $institutionId = null, array $filters = []): array
    {
        $query = InventoryAssetMovement::with(['asset', 'item.category', 'fromRoom', 'toRoom', 'creator']);

        if ($institutionId) {
            $query->where('institution_id', $institutionId);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('movement_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('movement_date', '<=', $filters['date_to']);
        }
        if (! empty($filters['room_id'])) {
            $roomId = (int) $filters['room_id'];
            $query->where(function ($q) use ($roomId) {
                $q->where('from_room_id', $roomId)->orWhere('to_room_id', $roomId);
            });
        }
        if (! empty($filters['category_id'])) {
            $query->whereHas('item', fn ($q) => $q->where('category_id', $filters['category_id']));
        }

        $movements = $query->orderByDesc('movement_date')->orderByDesc('id')->limit(5000)->get();

        return [
            'total' => $movements->count(),
            'movements' => $movements->map(fn ($m) => [
                'id' => $m->id,
                'movement_date' => $m->movement_date?->format('Y-m-d'),
                'asset_number' => $m->asset?->asset_number ?? '-',
                'item_code' => $m->item?->code ?? '-',
                'item_name' => $m->item?->name ?? '-',
                'category' => $m->item?->category?->name ?? '-',
                'from_location' => $m->fromRoom?->name,
                'to_location' => $m->toRoom?->name,
                'reference_number' => $m->reference_number,
                'notes' => $m->notes,
                'created_by' => $m->creator?->name ?? '-',
            ])->toArray(),
        ];
    }
}
