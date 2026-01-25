<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\InventoryCategory;
use App\Repositories\InventoryRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(
        private InventoryRepository $repository
    ) {}

    /**
     * Get list of inventory items with filters.
     */
    public function list(array $filters, ?int $institutionId = null, int $perPage = 15)
    {
        return $this->repository->list($filters, $institutionId, $perPage);
    }

    /**
     * Create a new inventory item.
     */
    public function create(array $data, int $userId, $file = null): InventoryItem
    {
        return DB::transaction(function () use ($data, $userId, $file) {
            $data['created_by'] = $userId;
            $data['status'] = $data['status'] ?? 'Tersedia';

            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->generateItemCode(
                    $data['institution_id'],
                    $data['category_id'],
                    $data['custom_code'] ?? null // Kode khusus seperti BKBA
                );
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

            // Create initial transaction (Masuk)
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

            Log::info('Inventory item created', [
                'item_id' => $item->id,
                'institution_id' => $data['institution_id'],
                'code' => $item->code,
            ]);

            return $item->load(['category', 'room', 'building', 'institution']);
        });
    }

    /**
     * Update inventory item.
     */
    public function update(InventoryItem $item, array $data, int $userId, $file = null): InventoryItem
    {
        return DB::transaction(function () use ($item, $data, $userId, $file) {
            $data['updated_by'] = $userId;

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

    /**
     * Record inventory transaction.
     */
    public function recordTransaction(array $data, int $userId): InventoryTransaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['created_by'] = $userId;

            $transaction = InventoryTransaction::create($data);

            // Update item quantity based on transaction type
            $item = InventoryItem::findOrFail($data['item_id']);
            
            if ($data['transaction_type'] === 'Masuk') {
                $item->increment('quantity', $data['quantity']);
            } elseif ($data['transaction_type'] === 'Keluar') {
                if ($item->quantity < $data['quantity']) {
                    throw new \Exception('Jumlah barang tidak mencukupi');
                }
                $item->decrement('quantity', $data['quantity']);
            } elseif ($data['transaction_type'] === 'Mutasi') {
                // Update location
                if (isset($data['to_location_id'])) {
                    $item->update(['room_id' => $data['to_location_id']]);
                }
            } elseif ($data['transaction_type'] === 'Penyesuaian') {
                // Direct quantity update
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
     * Generate item code: KODE_KATEGORI/NOMOR_URUT+KODE_KHUSUS/NPSN/TAHUN
     * Example: MEU/001BKBA/10816663/2026
     */
    private function generateItemCode(int $institutionId, int $categoryId, ?string $customCode = null): string
    {
        $institution = \App\Models\Institution::findOrFail($institutionId);
        $category = InventoryCategory::findOrFail($categoryId);
        
        if (empty($institution->npsn)) {
            throw new \Exception('NPSN institusi belum diatur. Silakan lengkapi data institusi terlebih dahulu.');
        }

        $year = date('Y');
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
}
