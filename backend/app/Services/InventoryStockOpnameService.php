<?php

namespace App\Services;

use App\Models\InventoryAsset;
use App\Models\InventoryAssetOpnameLine;
use App\Models\InventoryItem;
use App\Models\InventoryStockOpname;
use App\Models\InventoryStockOpnameLine;
use App\Support\InventoryCatalog;
use Illuminate\Support\Facades\DB;

class InventoryStockOpnameService
{
    public function __construct(
        private InventoryService $inventoryService
    ) {}

    public function list(array $filters, int $institutionId, int $perPage = 15)
    {
        $query = InventoryStockOpname::with(['room', 'building', 'creator'])
            ->withCount([
                'lines as stock_lines_count',
                'lines as counted_stock_lines_count' => fn ($q) => $q->whereNotNull('counted_quantity'),
                'assetLines as asset_lines_count',
                'assetLines as counted_asset_lines_count' => fn ($q) => $q->whereNotNull('found'),
            ])
            ->where('institution_id', $institutionId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['opname_type'])) {
            $query->where('opname_type', $filters['opname_type']);
        }
        if (! empty($filters['room_id'])) {
            $query->where('room_id', $filters['room_id']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('opname_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('opname_date')->orderByDesc('id')->paginate($perPage);
    }

    public function create(array $data, int $institutionId, int $userId): InventoryStockOpname
    {
        $opnameType = ($data['opname_type'] ?? 'stock') === 'asset' ? 'asset' : 'stock';

        return DB::transaction(function () use ($data, $institutionId, $userId, $opnameType) {
            $opname = InventoryStockOpname::create([
                'institution_id' => $institutionId,
                'opname_number' => $this->generateOpnameNumber($institutionId),
                'opname_date' => $data['opname_date'] ?? now()->toDateString(),
                'room_id' => $data['room_id'] ?? null,
                'building_id' => $data['building_id'] ?? null,
                'status' => 'draft',
                'opname_type' => $opnameType,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            if ($opnameType === 'asset') {
                $this->populateAssetLines($opname, $userId);
            } else {
                $this->populateLines($opname, $userId);
            }

            return $this->freshOpname($opname);
        });
    }

    public function populateLines(InventoryStockOpname $opname, int $userId): void
    {
        if (! $opname->isEditable()) {
            throw new \Exception('Opname tidak dapat diubah.');
        }
        if ($opname->isAssetOpname()) {
            throw new \Exception('Sesi ini adalah opname aset individual.');
        }

        $query = InventoryItem::where('institution_id', $opname->institution_id)
            ->where('tracking_type', InventoryCatalog::TRACKING_STOCK)
            ->whereNull('disposed_at');

        if ($opname->room_id) {
            $query->where('room_id', $opname->room_id);
        }

        $items = $query->orderBy('name')->get();
        $existingItemIds = $opname->lines()->pluck('item_id')->all();

        foreach ($items as $item) {
            if (in_array($item->id, $existingItemIds, true)) {
                $line = $opname->lines()->where('item_id', $item->id)->first();
                if ($line && $line->counted_quantity === null) {
                    $line->update(['book_quantity' => (int) $item->quantity]);
                }
                continue;
            }

            InventoryStockOpnameLine::create([
                'opname_id' => $opname->id,
                'item_id' => $item->id,
                'book_quantity' => (int) $item->quantity,
            ]);
        }

        if ($opname->status === 'draft') {
            $opname->update(['status' => 'in_progress', 'updated_by' => $userId]);
        }
    }

    public function populateAssetLines(InventoryStockOpname $opname, int $userId): void
    {
        if (! $opname->isEditable()) {
            throw new \Exception('Opname tidak dapat diubah.');
        }
        if (! $opname->isAssetOpname()) {
            throw new \Exception('Sesi ini bukan opname aset individual.');
        }

        $query = InventoryAsset::query()
            ->where('institution_id', $opname->institution_id)
            ->where('disposal_status', InventoryCatalog::DISPOSAL_ACTIVE)
            ->whereNull('disposed_at');

        if ($opname->room_id) {
            $query->where('room_id', $opname->room_id);
        }

        $assets = $query->orderBy('asset_number')->get();
        $existingAssetIds = $opname->assetLines()->pluck('asset_id')->all();

        foreach ($assets as $asset) {
            if (in_array($asset->id, $existingAssetIds, true)) {
                $line = $opname->assetLines()->where('asset_id', $asset->id)->first();
                if ($line && $line->found === null) {
                    $line->update([
                        'book_status' => $asset->status,
                        'book_condition' => $asset->condition,
                    ]);
                }
                continue;
            }

            InventoryAssetOpnameLine::create([
                'opname_id' => $opname->id,
                'asset_id' => $asset->id,
                'book_status' => $asset->status,
                'book_condition' => $asset->condition,
            ]);
        }

        if ($opname->status === 'draft') {
            $opname->update(['status' => 'in_progress', 'updated_by' => $userId]);
        }
    }

    public function updateLine(InventoryStockOpnameLine $line, array $data, int $userId): InventoryStockOpnameLine
    {
        $opname = $line->opname;
        if (! $opname || ! $opname->isEditable()) {
            throw new \Exception('Opname sudah difinalisasi atau dibatalkan.');
        }

        if (array_key_exists('counted_quantity', $data)) {
            $counted = $data['counted_quantity'];
            if ($counted !== null && $counted < 0) {
                throw new \Exception('Jumlah fisik tidak valid.');
            }
            $line->counted_quantity = $counted;
            $line->variance = $counted !== null ? ((int) $counted - (int) $line->book_quantity) : null;
        }

        foreach (['condition', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $line->{$field} = $data[$field];
            }
        }

        $line->save();
        $opname->update(['updated_by' => $userId]);

        return $line->fresh(['item.category']);
    }

    public function updateAssetLine(InventoryAssetOpnameLine $line, array $data, int $userId): InventoryAssetOpnameLine
    {
        $opname = $line->opname;
        if (! $opname || ! $opname->isEditable()) {
            throw new \Exception('Opname sudah difinalisasi atau dibatalkan.');
        }

        if (array_key_exists('found', $data)) {
            $line->found = $data['found'];
        }
        foreach (['counted_condition', 'notes'] as $field) {
            if (array_key_exists($field, $data)) {
                $line->{$field} = $data[$field];
            }
        }

        $line->save();
        $opname->update(['updated_by' => $userId]);

        return $line->fresh(['asset.item', 'asset.room']);
    }

    public function finalize(InventoryStockOpname $opname, int $userId): InventoryStockOpname
    {
        return $opname->isAssetOpname()
            ? $this->finalizeAssetOpname($opname, $userId)
            : $this->finalizeStockOpname($opname, $userId);
    }

    protected function finalizeStockOpname(InventoryStockOpname $opname, int $userId): InventoryStockOpname
    {
        return DB::transaction(function () use ($opname, $userId) {
            $opname = InventoryStockOpname::lockForUpdate()->findOrFail($opname->id);

            if (! $opname->isEditable()) {
                throw new \Exception('Opname sudah difinalisasi atau dibatalkan.');
            }

            $lines = $opname->lines()->with('item')->get();
            if ($lines->isEmpty()) {
                throw new \Exception('Tidak ada baris opname.');
            }

            $uncounted = $lines->whereNull('counted_quantity')->count();
            if ($uncounted > 0) {
                throw new \Exception("Masih ada {$uncounted} barang belum dihitung fisiknya.");
            }

            foreach ($lines as $line) {
                if ((int) $line->variance === 0) {
                    continue;
                }

                $transaction = $this->inventoryService->recordTransaction([
                    'institution_id' => $opname->institution_id,
                    'item_id' => $line->item_id,
                    'transaction_type' => 'Penyesuaian',
                    'transaction_date' => $opname->opname_date->format('Y-m-d'),
                    'quantity' => (int) $line->counted_quantity,
                    'reference_number' => $opname->opname_number,
                    'notes' => 'Stock opname #' . $opname->opname_number . ($line->notes ? ': ' . $line->notes : ''),
                ], $userId);

                $line->update(['adjustment_transaction_id' => $transaction->id]);

                if ($line->condition && $line->item) {
                    $line->item->update([
                        'condition' => $line->condition,
                        'updated_by' => $userId,
                    ]);
                }
            }

            $opname->update([
                'status' => 'finalized',
                'finalized_at' => now(),
                'updated_by' => $userId,
            ]);

            return $this->freshOpname($opname);
        });
    }

    protected function finalizeAssetOpname(InventoryStockOpname $opname, int $userId): InventoryStockOpname
    {
        return DB::transaction(function () use ($opname, $userId) {
            $opname = InventoryStockOpname::lockForUpdate()->findOrFail($opname->id);

            if (! $opname->isEditable()) {
                throw new \Exception('Opname sudah difinalisasi atau dibatalkan.');
            }

            $lines = $opname->assetLines()->with('asset')->get();
            if ($lines->isEmpty()) {
                throw new \Exception('Tidak ada aset dalam sesi opname.');
            }

            $unchecked = $lines->whereNull('found')->count();
            if ($unchecked > 0) {
                throw new \Exception("Masih ada {$unchecked} aset belum dicek.");
            }

            foreach ($lines as $line) {
                $asset = $line->asset;
                if (! $asset) {
                    continue;
                }

                $updates = ['updated_by' => $userId];

                if ($line->found === false) {
                    $updates['status'] = 'Hilang';
                }

                if ($line->counted_condition) {
                    $updates['condition'] = $line->counted_condition;
                }

                if (count($updates) > 1) {
                    $asset->update($updates);
                }
            }

            $opname->update([
                'status' => 'finalized',
                'finalized_at' => now(),
                'updated_by' => $userId,
            ]);

            return $this->freshOpname($opname);
        });
    }

    public function cancel(InventoryStockOpname $opname, int $userId): InventoryStockOpname
    {
        if (! $opname->isEditable()) {
            throw new \Exception('Opname sudah difinalisasi.');
        }

        $opname->update([
            'status' => 'cancelled',
            'updated_by' => $userId,
        ]);

        return $this->freshOpname($opname);
    }

    public function generateOpnameNumber(int $institutionId): string
    {
        $year = date('Y');
        $prefix = "OPNAME/{$year}/";

        $last = InventoryStockOpname::where('institution_id', $institutionId)
            ->where('opname_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('opname_number');

        $sequence = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $sequence = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }

    protected function freshOpname(InventoryStockOpname $opname): InventoryStockOpname
    {
        if ($opname->isAssetOpname()) {
            return $opname->fresh([
                'assetLines.asset.item',
                'assetLines.asset.room',
                'room',
                'building',
                'creator',
            ]);
        }

        return $opname->fresh([
            'lines.item.category',
            'lines.adjustmentTransaction',
            'room',
            'building',
            'creator',
        ]);
    }
}
