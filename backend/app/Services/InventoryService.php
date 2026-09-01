<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryDisposal;
use App\Models\InventoryAsset;
use App\Models\InventoryTransaction;
use App\Models\InventoryCategory;
use App\Models\Room;
use App\Repositories\InventoryRepository;
use App\Support\InventoryCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(
        private InventoryRepository $repository,
        private InventoryAssetService $assetService
    ) {}

    /**
     * Get list of inventory items with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15)
    {
        return $this->repository->list($filters, $institutionId, $perPage);
    }

    public function listForExport(array $filters, ?int $institutionId = null, int $limit = 5000)
    {
        return $this->repository->listForExport($filters, $institutionId, $limit);
    }

    /**
     * Create a new inventory item.
     */
    public function create(array $data, int $userId, $file = null): InventoryItem
    {
        return DB::transaction(function () use ($data, $userId, $file) {
            $data['created_by'] = $userId;
            $data['status'] = $data['status'] ?? 'Tersedia';
            $data['tracking_type'] = $data['tracking_type'] ?? InventoryCatalog::TRACKING_STOCK;
            $data['identity_status'] = $data['identity_status'] ?? InventoryCatalog::IDENTITY_COMPLETE;

            if ($data['tracking_type'] === InventoryCatalog::TRACKING_INDIVIDUAL) {
                $data['identity_status'] = InventoryCatalog::IDENTITY_COMPLETE;
            }

            $this->syncBuildingFromRoom($data);

            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateItemCode(
                    $data['institution_id'],
                    $data['category_id'],
                    $data['custom_code'] ?? null,
                    $data['purchase_date'] ?? null
                );
            }

            $data['master_code'] = $data['master_code'] ?? $data['code'];
            $data['legacy_code'] = $data['legacy_code'] ?? $data['code'];

            $assetCount = 1;
            if ($data['tracking_type'] === InventoryCatalog::TRACKING_INDIVIDUAL) {
                $assetCount = max(1, (int) ($data['quantity'] ?? 1));
                $data['quantity'] = 0;
                // Serial number di master individual sebaiknya kosong (identitas per aset)
                if (! empty($data['serial_number'])) {
                    $data['serial_number'] = null;
                }
            }

            // Handle image upload
            if ($file) {
                try {
                    // Sanitize file name to prevent path traversal
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                    $fileName = time() . '_' . $safeName . '.' . $extension;
                    $filePath = $file->storeAs(
                        'inventory/' . $data['institution_id'],
                        $fileName,
                        'public'
                    );
                    $data['image_path'] = $filePath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload image', [
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception('Gagal mengunggah gambar: ' . $e->getMessage());
                }
            }

            $item = InventoryItem::create($data);

            if ($item->isStockTracked()) {
                // Create initial transaction (Masuk) untuk stok
                if (isset($data['quantity']) && $data['quantity'] > 0) {
                    InventoryTransaction::create([
                        'institution_id' => $data['institution_id'],
                        'item_id' => $item->id,
                        'transaction_type' => 'Masuk',
                        'transaction_date' => $data['purchase_date'] ?? now(),
                        'quantity' => $data['quantity'] ?? 1,
                        'reference_number' => $data['reference_number'] ?? null,
                        'notes' => 'Barang baru ditambahkan ke inventaris',
                        'created_by' => $userId,
                    ]);
                }
            } else {
                $this->assetService->createForItem($item, $assetCount, $userId);
            }

            Log::info('Inventory item created', [
                'item_id' => $item->id,
                'institution_id' => $data['institution_id'],
                'code' => $item->code,
                'tracking_type' => $item->tracking_type,
            ]);

            return $item->fresh(['category', 'room', 'building', 'institution', 'assets']);
        });
    }

    /**
     * Update inventory item.
     */
    public function update(InventoryItem $item, array $data, int $userId, $file = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $data, $userId, $file) {
            $data['updated_by'] = $userId;

            if (array_key_exists('quantity', $data)) {
                throw new \Exception(
                    'Perubahan jumlah stok harus melalui transaksi Masuk/Keluar/Penyesuaian, bukan edit barang langsung.'
                );
            }

            if (array_key_exists('tracking_type', $data) && $data['tracking_type'] !== $item->tracking_type) {
                if ($item->assets()->exists()) {
                    throw new \Exception('Tipe pelacakan tidak dapat diubah setelah ada aset individual.');
                }
            }

            $this->syncBuildingFromRoom($data);

            // Handle image upload
            if ($file) {
                // Delete old image
                if ($item->image_path && Storage::disk('public')->exists($item->image_path)) {
                    Storage::disk('public')->delete($item->image_path);
                }

                try {
                    // Sanitize file name to prevent path traversal
                    $originalName = $file->getClientOriginalName();
                    $extension = $file->getClientOriginalExtension();
                    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
                    $fileName = time() . '_' . $safeName . '.' . $extension;
                    $filePath = $file->storeAs(
                        'inventory/' . $item->institution_id,
                        $fileName,
                        'public'
                    );
                    $data['image_path'] = $filePath;
                } catch (\Exception $e) {
                    Log::error('Failed to upload image', [
                        'error' => $e->getMessage(),
                    ]);
                    throw new \Exception('Gagal mengunggah gambar: ' . $e->getMessage());
                }
            }

            $item->update($data);

            Log::info('Inventory item updated', [
                'item_id' => $item->id,
            ]);

            return $item->fresh(['category', 'room', 'building', 'institution']);
        });
    }

    /**
     * Delete inventory item.
     */
    public function delete(InventoryItem $item, int $userId): bool
    {
        return DB::transaction(function () use ($item, $userId) {
            return $item->delete();
        });
    }

    public function dispose(InventoryItem $item, array $data, int $userId): InventoryItem
    {
        if ($item->isIndividualTracked()) {
            throw new \Exception('Penghapusan master aset individual dilakukan per unit aset, bukan dari master barang.');
        }

        return DB::transaction(function () use ($item, $data, $userId) {
            $item = InventoryItem::lockForUpdate()->findOrFail($item->id);

            if ($item->disposed_at && (int) $item->quantity <= 0) {
                throw new \Exception('Barang ini sudah dihapus sepenuhnya.');
            }

            if ($item->quantity < 1) {
                throw new \Exception('Jumlah barang tidak valid untuk penghapusan.');
            }

            $disposalDate = $data['disposal_date'] ?? now()->toDateString();
            $finalStatus = $data['status'] ?? 'Dijual';
            $qty = (int) ($data['quantity'] ?? $item->quantity);

            if ($qty < 1 || $qty > $item->quantity) {
                throw new \Exception('Jumlah penghapusan tidak valid.');
            }

            $transaction = $this->recordTransaction([
                'institution_id' => $item->institution_id,
                'item_id' => $item->id,
                'transaction_type' => 'Keluar',
                'transaction_date' => $disposalDate,
                'quantity' => $qty,
                'reference_number' => $data['disposal_document_number'] ?? null,
                'notes' => 'Penghapusan: ' . ($data['disposal_reason'] ?? '-'),
            ], $userId);

            InventoryDisposal::create([
                'institution_id' => $item->institution_id,
                'item_id' => $item->id,
                'transaction_id' => $transaction->id,
                'quantity' => $qty,
                'disposal_date' => $disposalDate,
                'status' => $finalStatus,
                'disposal_reason' => $data['disposal_reason'] ?? '-',
                'disposal_document_number' => $data['disposal_document_number'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $item->refresh();

            if ($item->quantity <= 0) {
                $updates = [
                    'status' => $finalStatus,
                    'disposed_at' => $disposalDate,
                    'quantity' => 0,
                    'disposal_reason' => $data['disposal_reason'] ?? null,
                    'disposal_document_number' => $data['disposal_document_number'] ?? null,
                    'updated_by' => $userId,
                ];
                if (empty($data['condition']) && in_array($finalStatus, ['Dijual', 'Hilang'], true)) {
                    $updates['condition'] = $finalStatus === 'Hilang' ? $item->condition : 'Habis Pakai';
                } elseif (!empty($data['condition'])) {
                    $updates['condition'] = $data['condition'];
                }
                $item->update($updates);
            } else {
                $item->update(['updated_by' => $userId]);
            }

            Log::info('Inventory item disposed', [
                'item_id' => $item->id,
                'quantity' => $qty,
                'status' => $finalStatus,
            ]);

            return $item->fresh(['category', 'room', 'building', 'institution', 'responsibleEmployee']);
        });
    }

    public function updateDisposal(InventoryDisposal $disposal, array $data, int $userId): InventoryDisposal
    {
        return DB::transaction(function () use ($disposal, $data, $userId) {
            $disposal = InventoryDisposal::lockForUpdate()->findOrFail($disposal->id);
            $item = InventoryItem::lockForUpdate()->findOrFail($disposal->item_id);

            if ($disposal->asset_id) {
                foreach (['disposal_date', 'disposal_reason', 'disposal_document_number', 'status'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $disposal->{$field} = $data[$field];
                    }
                }
                $disposal->updated_by = $userId;
                $disposal->save();

                $asset = InventoryAsset::lockForUpdate()->find($disposal->asset_id);
                if ($asset) {
                    $assetUpdates = ['updated_by' => $userId];
                    if (isset($data['disposal_date'])) {
                        $assetUpdates['disposed_at'] = $data['disposal_date'];
                    }
                    if (array_key_exists('disposal_reason', $data)) {
                        $assetUpdates['disposal_reason'] = $data['disposal_reason'];
                    }
                    if (array_key_exists('disposal_document_number', $data)) {
                        $assetUpdates['disposal_document_number'] = $data['disposal_document_number'];
                    }
                    if (isset($data['status'])) {
                        $assetUpdates['status'] = $data['status'];
                    }
                    $asset->update($assetUpdates);
                }

                return $disposal->fresh(['item.category', 'item.room', 'asset']);
            }

            if (isset($data['quantity']) && (int) $data['quantity'] !== (int) $disposal->quantity) {
                $newQty = (int) $data['quantity'];
                $diff = $newQty - (int) $disposal->quantity;

                if ($diff > 0) {
                    if ($item->quantity < $diff) {
                        throw new \Exception('Stok barang tidak mencukupi untuk menambah jumlah penghapusan.');
                    }
                    $this->recordTransaction([
                        'institution_id' => $item->institution_id,
                        'item_id' => $item->id,
                        'transaction_type' => 'Keluar',
                        'transaction_date' => $data['disposal_date'] ?? $disposal->disposal_date->format('Y-m-d'),
                        'quantity' => $diff,
                        'reference_number' => $data['disposal_document_number'] ?? $disposal->disposal_document_number,
                        'notes' => 'Koreksi penghapusan #' . $disposal->id,
                    ], $userId);
                } elseif ($diff < 0) {
                    $this->recordTransaction([
                        'institution_id' => $item->institution_id,
                        'item_id' => $item->id,
                        'transaction_type' => 'Masuk',
                        'transaction_date' => $data['disposal_date'] ?? $disposal->disposal_date->format('Y-m-d'),
                        'quantity' => abs($diff),
                        'notes' => 'Koreksi penghapusan #' . $disposal->id,
                    ], $userId);
                }

                $disposal->quantity = $newQty;
                $item->refresh();
                $this->syncItemDisposalState($item, $disposal, $userId);
            }

            foreach (['disposal_date', 'disposal_reason', 'disposal_document_number', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $disposal->{$field} = $data[$field];
                }
            }
            $disposal->updated_by = $userId;
            $disposal->save();

            $item->refresh();
            if ($item->disposed_at) {
                $item->update([
                    'disposal_reason' => $disposal->disposal_reason,
                    'disposal_document_number' => $disposal->disposal_document_number,
                    'status' => $disposal->status,
                    'disposed_at' => $disposal->disposal_date,
                    'updated_by' => $userId,
                ]);
            }

            return $disposal->fresh(['item.category', 'item.room', 'asset']);
        });
    }

    public function undoDisposal(InventoryDisposal $disposal, int $userId): void
    {
        DB::transaction(function () use ($disposal, $userId) {
            $disposal = InventoryDisposal::lockForUpdate()->findOrFail($disposal->id);
            $item = InventoryItem::lockForUpdate()->findOrFail($disposal->item_id);

            if ($disposal->asset_id) {
                $asset = InventoryAsset::lockForUpdate()->findOrFail($disposal->asset_id);
                $asset->update([
                    'disposal_status' => InventoryCatalog::DISPOSAL_ACTIVE,
                    'disposed_at' => null,
                    'disposal_reason' => null,
                    'disposal_document_number' => null,
                    'status' => 'Tersedia',
                    'updated_by' => $userId,
                ]);
                $this->assetService->syncItemQuantityFromAssets($item->fresh());
            } else {
                $this->recordTransaction([
                    'institution_id' => $item->institution_id,
                    'item_id' => $item->id,
                    'transaction_type' => 'Masuk',
                    'transaction_date' => now()->toDateString(),
                    'quantity' => $disposal->quantity,
                    'reference_number' => $disposal->disposal_document_number,
                    'notes' => 'Pembatalan penghapusan #' . $disposal->id,
                ], $userId);

                $item->refresh();
                $updates = ['updated_by' => $userId];
                if ($item->disposed_at) {
                    $updates['disposed_at'] = null;
                    $updates['disposal_reason'] = null;
                    $updates['disposal_document_number'] = null;
                    $updates['status'] = 'Tersedia';
                }
                $item->update($updates);
            }

            $disposal->updated_by = $userId;
            $disposal->save();
            $this->deleteDisposalDocumentFile($disposal);
            $disposal->delete();
        });
    }

    protected function syncItemDisposalState(InventoryItem $item, InventoryDisposal $disposal, int $userId): void
    {
        if ($item->quantity > 0 && $item->disposed_at) {
            $item->update([
                'disposed_at' => null,
                'disposal_reason' => null,
                'disposal_document_number' => null,
                'status' => 'Tersedia',
                'updated_by' => $userId,
            ]);
        } elseif ($item->quantity <= 0 && !$item->disposed_at) {
            $item->update([
                'disposed_at' => $disposal->disposal_date,
                'disposal_reason' => $disposal->disposal_reason,
                'disposal_document_number' => $disposal->disposal_document_number,
                'status' => $disposal->status,
                'quantity' => 0,
                'updated_by' => $userId,
            ]);
        }
    }

    /**
     * Record inventory transaction.
     */
    public function recordTransaction(array $data, int $userId): InventoryTransaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['created_by'] = $userId;

            $item = InventoryItem::findOrFail($data['item_id']);

            if ($item->isIndividualTracked()) {
                throw new \Exception('Transaksi stok tidak berlaku untuk master aset individual. Gunakan modul aset.');
            }

            if ($data['transaction_type'] === 'Mutasi' && empty($data['from_location_id']) && $item->room_id) {
                $data['from_location_id'] = $item->room_id;
            }

            if ($data['transaction_type'] === 'Penyesuaian') {
                $previousQty = (int) $item->quantity;
                $newQty = (int) $data['quantity'];
                if ($newQty < 0) {
                    throw new \Exception('Jumlah penyesuaian tidak valid.');
                }
                $data['notes'] = trim(($data['notes'] ?? '') . " (Penyesuaian: {$previousQty} → {$newQty})");
            }

            $transaction = InventoryTransaction::create($data);

            if ($data['transaction_type'] === 'Masuk') {
                $item->increment('quantity', $data['quantity']);
            } elseif ($data['transaction_type'] === 'Keluar') {
                if ($item->quantity < $data['quantity']) {
                    throw new \Exception('Jumlah barang tidak mencukupi');
                }
                $item->decrement('quantity', $data['quantity']);
            } elseif ($data['transaction_type'] === 'Mutasi') {
                if (isset($data['to_location_id'])) {
                    $room = Room::find($data['to_location_id']);
                    $update = ['room_id' => $data['to_location_id']];
                    if ($room) {
                        $update['building_id'] = $room->building_id;
                    }
                    $item->update($update);
                }
            } elseif ($data['transaction_type'] === 'Penyesuaian') {
                $item->update(['quantity' => $data['quantity']]);
            }

            Log::info('Inventory transaction recorded', [
                'transaction_id' => $transaction->id,
                'item_id' => $data['item_id'],
                'type' => $data['transaction_type'],
            ]);

            return $transaction->load(['item', 'creator']);
        });
    }

    /**
     * Sync building_id from room_id: when an item is assigned to a room,
     * set building_id from that room so location data stays consistent.
     */
    private function syncBuildingFromRoom(array &$data): void
    {
        if (array_key_exists('room_id', $data)) {
            if (!empty($data['room_id'])) {
                $room = Room::find($data['room_id']);
                if ($room) {
                    $data['building_id'] = $room->building_id;
                }
            } else {
                $data['building_id'] = null;
            }
        }
    }

    /**
     * Generate item code: KODE_KATEGORI/NOMOR_URUT+KODE_KHUSUS/NPSN/TAHUN
     * Example: MEU/001BKBA/10816663/2023
     * Tahun mengikuti tanggal beli (purchase_date); fallback ke tahun berjalan.
     */
    private function generateItemCode(
        int $institutionId,
        int $categoryId,
        ?string $customCode = null,
        ?string $purchaseDate = null
    ): string {
        $institution = \App\Models\Institution::findOrFail($institutionId);
        $category = InventoryCategory::findOrFail($categoryId);
        
        if (empty($institution->npsn)) {
            throw new \Exception('NPSN institusi belum diatur. Silakan lengkapi data institusi terlebih dahulu.');
        }

        $year = $purchaseDate ? date('Y', strtotime($purchaseDate)) : date('Y');
        $categoryCode = $category->code;

        // Get last item in this category for this year
        $lastItem = InventoryItem::where('institution_id', $institutionId)
            ->where('category_id', $categoryId)
            ->where('code', 'like', $categoryCode . '/%/' . $institution->npsn . '/' . $year)
            ->orderBy('id', 'desc')
            ->first();

        // Extract sequence number from last code
        $sequence = 1;
        if ($lastItem && $lastItem->code) {
            // Format: MEU/001BKBA/10816663/2026
            // Extract: 001BKBA -> get 001
            preg_match('/\/0*(\d+)/', $lastItem->code, $matches);
            if (isset($matches[1])) {
                $sequence = (int)$matches[1] + 1;
            }
        }

        // Format: KODE/NOMOR+KODE_KHUSUS/NPSN/TAHUN
        $sequenceStr = str_pad($sequence, 3, '0', STR_PAD_LEFT);
        $customCodePart = $customCode ? $customCode : '';
        
        return sprintf('%s/%s%s/%s/%s', 
            $categoryCode,
            $sequenceStr,
            $customCodePart,
            $institution->npsn,
            $year
        );
    }

    public function storeDisposalDocument(InventoryDisposal $disposal, \Illuminate\Http\UploadedFile $file, int $userId): InventoryDisposal
    {
        $this->deleteDisposalDocumentFile($disposal);

        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = time() . '_' . $safeName . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs(
            'inventory/disposals/' . $disposal->institution_id . '/' . $disposal->id,
            $fileName,
            'public'
        );

        $disposal->update([
            'document_path' => $path,
            'document_name' => $file->getClientOriginalName(),
            'document_size' => $file->getSize(),
            'document_mime' => $file->getMimeType(),
            'updated_by' => $userId,
        ]);

        return $disposal->fresh(['item.category', 'item.room', 'asset']);
    }

    public function deleteDisposalDocument(InventoryDisposal $disposal, int $userId): InventoryDisposal
    {
        $this->deleteDisposalDocumentFile($disposal);
        $disposal->update([
            'document_path' => null,
            'document_name' => null,
            'document_size' => null,
            'document_mime' => null,
            'updated_by' => $userId,
        ]);

        return $disposal->fresh(['item.category', 'item.room', 'asset']);
    }

    protected function deleteDisposalDocumentFile(InventoryDisposal $disposal): void
    {
        if ($disposal->document_path) {
            Storage::disk('public')->delete($disposal->document_path);
        }
    }
}
