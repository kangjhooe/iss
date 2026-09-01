<?php

namespace App\Services;

use App\Models\InventoryAsset;
use App\Models\InventoryAssetMovement;
use App\Models\InventoryDisposal;
use App\Models\InventoryItem;
use App\Models\Room;
use App\Repositories\InventoryAssetRepository;
use App\Support\InventoryCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class InventoryAssetService
{
    public const QR_TOKEN_PREFIX = 'ISS1';

    public function __construct(
        private InventoryAssetRepository $repository
    ) {}

    public function list(array $filters, ?int $institutionId = null, int $perPage = 15)
    {
        return $this->repository->list($filters, $institutionId, $perPage);
    }

    /**
     * Buat satu atau banyak aset individual untuk master barang.
     *
     * @return list<InventoryAsset>
     */
    public function createForItem(InventoryItem $item, int $count, int $userId, array $defaults = []): array
    {
        if ($item->tracking_type !== InventoryCatalog::TRACKING_INDIVIDUAL) {
            throw new \InvalidArgumentException('Master barang bukan tipe aset individual.');
        }

        if ($count < 1 || $count > 500) {
            throw new \InvalidArgumentException('Jumlah aset harus antara 1 dan 500.');
        }

        return DB::transaction(function () use ($item, $count, $userId, $defaults) {
            $created = [];
            for ($i = 0; $i < $count; $i++) {
                $created[] = $this->createSingleAsset($item, $userId, $defaults);
            }

            $this->syncItemQuantityFromAssets($item);

            if ($item->identity_status === InventoryCatalog::IDENTITY_MIGRATION_PENDING) {
                $item->update([
                    'identity_status' => InventoryCatalog::IDENTITY_COMPLETE,
                    'updated_by' => $userId,
                ]);
            }

            Log::info('Inventory assets created', [
                'item_id' => $item->id,
                'count' => $count,
            ]);

            return $created;
        });
    }

    public function createSingleAsset(InventoryItem $item, int $userId, array $overrides = []): InventoryAsset
    {
        $roomId = $overrides['room_id'] ?? $item->room_id;
        $buildingId = $overrides['building_id'] ?? $item->building_id;

        if ($roomId && empty($buildingId)) {
            $room = Room::find($roomId);
            $buildingId = $room?->building_id;
        }

        $assetNumber = $this->generateAssetNumber($item->institution_id);

        $asset = InventoryAsset::create([
            'institution_id' => $item->institution_id,
            'item_id' => $item->id,
            'asset_number' => $assetNumber,
            'inventory_number' => $overrides['inventory_number'] ?? $this->buildInventoryNumber($item, $assetNumber),
            'serial_number' => $overrides['serial_number'] ?? null,
            'registration_number' => $overrides['registration_number'] ?? null,
            'condition' => $overrides['condition'] ?? $item->condition ?? 'Baik',
            'status' => $overrides['status'] ?? 'Tersedia',
            'disposal_status' => InventoryCatalog::DISPOSAL_ACTIVE,
            'room_id' => $roomId,
            'building_id' => $buildingId,
            'responsible_employee_id' => $overrides['responsible_employee_id'] ?? $item->responsible_employee_id,
            'location_start_date' => $overrides['location_start_date'] ?? ($roomId ? now()->toDateString() : null),
            'responsible_start_date' => $overrides['responsible_start_date'] ?? null,
            'purchase_date' => $overrides['purchase_date'] ?? $item->purchase_date,
            'purchase_price' => $overrides['purchase_price'] ?? $item->purchase_price,
            'location_note' => $overrides['location_note'] ?? $item->location_note,
            'description' => $overrides['description'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $asset->update([
            'qr_token' => $this->makeQrToken($asset->id, $item->institution_id),
        ]);

        return $asset->fresh(['item.category', 'room', 'building', 'responsibleEmployee']);
    }

    public function update(InventoryAsset $asset, array $data, int $userId): InventoryAsset
    {
        return DB::transaction(function () use ($asset, $data, $userId) {
            if (array_key_exists('room_id', $data)) {
                if (! empty($data['room_id'])) {
                    $room = Room::find($data['room_id']);
                    if ($room) {
                        $data['building_id'] = $room->building_id;
                    }
                } else {
                    $data['building_id'] = null;
                }
            }

            $data['updated_by'] = $userId;
            $asset->update($data);
            $this->syncItemQuantityFromAssets($asset->item);

            return $asset->fresh(['item.category', 'room', 'building', 'responsibleEmployee']);
        });
    }

    /**
     * Pecah master stok menjadi aset individual (manual, tidak auto-split data lama).
     */
    public function splitFromStockItem(InventoryItem $item, int $count, int $userId): array
    {
        if ($item->tracking_type !== InventoryCatalog::TRACKING_STOCK) {
            throw new \InvalidArgumentException('Hanya master stok yang dapat dipecah.');
        }

        if ($count < 1 || $count > $item->quantity) {
            throw new \InvalidArgumentException('Jumlah pecahan tidak valid.');
        }

        if ($count !== (int) $item->quantity) {
            throw new \InvalidArgumentException(
                'Pecahan unit saat ini hanya mendukung konversi seluruh stok sekaligus. Kurangi stok terlebih dahulu jika perlu.'
            );
        }

        return DB::transaction(function () use ($item, $count, $userId) {
            $item->update([
                'tracking_type' => InventoryCatalog::TRACKING_INDIVIDUAL,
                'identity_status' => InventoryCatalog::IDENTITY_COMPLETE,
                'master_code' => $item->master_code ?? $item->code,
                'legacy_code' => $item->legacy_code ?? $item->code,
                'updated_by' => $userId,
            ]);

            $defaults = [
                'room_id' => $item->room_id,
                'building_id' => $item->building_id,
                'responsible_employee_id' => $item->responsible_employee_id,
                'location_note' => $item->location_note,
                'purchase_date' => $item->purchase_date?->format('Y-m-d'),
                'purchase_price' => $item->purchase_price,
                'condition' => $item->condition,
            ];

            $assets = $this->createForItem($item->fresh(), $count, $userId, $defaults);

            // Kurangi quantity stok master — sisa tetap stok atau nol
            $remaining = max(0, (int) $item->quantity - $count);
            if ($remaining > 0) {
                // Jika masih ada sisa, buat duplikat master stok? Terlalu kompleks.
                // Untuk split: quantity master = jumlah aset yang dibuat
                $item->update(['quantity' => count($assets), 'updated_by' => $userId]);
            }

            $this->syncItemQuantityFromAssets($item->fresh());

            return $assets;
        });
    }

    public function dispose(InventoryAsset $asset, array $data, int $userId): InventoryAsset
    {
        return DB::transaction(function () use ($asset, $data, $userId) {
            $asset = InventoryAsset::lockForUpdate()->findOrFail($asset->id);

            if ($asset->disposal_status !== InventoryCatalog::DISPOSAL_ACTIVE || $asset->disposed_at) {
                throw new \Exception('Aset sudah dihapus.');
            }

            if ($asset->loans()->whereIn('status', ['Dipinjam', 'Terlambat'])->exists()) {
                throw new \Exception('Aset masih dipinjam, tidak dapat dihapus.');
            }

            $disposalDate = $data['disposal_date'] ?? now()->toDateString();
            $finalStatus = $data['status'] ?? 'Dijual';

            InventoryDisposal::create([
                'institution_id' => $asset->institution_id,
                'item_id' => $asset->item_id,
                'asset_id' => $asset->id,
                'transaction_id' => null,
                'quantity' => 1,
                'disposal_date' => $disposalDate,
                'status' => $finalStatus,
                'disposal_reason' => $data['disposal_reason'] ?? '-',
                'disposal_document_number' => $data['disposal_document_number'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $condition = $data['condition'] ?? null;
            if (! $condition && in_array($finalStatus, ['Dijual', 'Hilang'], true)) {
                $condition = $finalStatus === 'Hilang' ? $asset->condition : 'Habis Pakai';
            }

            $asset->update([
                'disposal_status' => InventoryCatalog::DISPOSAL_DISPOSED,
                'disposed_at' => $disposalDate,
                'disposal_reason' => $data['disposal_reason'] ?? null,
                'disposal_document_number' => $data['disposal_document_number'] ?? null,
                'status' => $finalStatus,
                'condition' => $condition ?? $asset->condition,
                'updated_by' => $userId,
            ]);

            $this->syncItemQuantityFromAssets($asset->item);

            Log::info('Inventory asset disposed', [
                'asset_id' => $asset->id,
                'status' => $finalStatus,
            ]);

            return $asset->fresh(['item.category', 'room', 'building', 'responsibleEmployee']);
        });
    }

    /**
     * Mutasi formal aset individual antar ruangan.
     */
    public function transfer(
        InventoryAsset $asset,
        int $toRoomId,
        int $userId,
        ?string $movementDate = null,
        ?string $referenceNumber = null,
        ?string $notes = null
    ): InventoryAsset {
        return DB::transaction(function () use ($asset, $toRoomId, $userId, $movementDate, $referenceNumber, $notes) {
            $asset = InventoryAsset::lockForUpdate()->findOrFail($asset->id);

            if ($asset->disposal_status !== InventoryCatalog::DISPOSAL_ACTIVE || $asset->disposed_at) {
                throw new \Exception('Aset sudah dihapus, tidak dapat dimutasi.');
            }

            if ($asset->status === 'Dipinjam') {
                throw new \Exception('Aset sedang dipinjam, mutasi ditolak.');
            }

            $room = Room::findOrFail($toRoomId);
            if ((int) $room->institution_id !== (int) $asset->institution_id) {
                throw new \Exception('Ruangan tujuan tidak valid.');
            }

            $fromRoomId = $asset->room_id;
            $fromBuildingId = $asset->building_id;

            if ((int) $fromRoomId === (int) $toRoomId) {
                throw new \Exception('Ruangan tujuan sama dengan lokasi saat ini.');
            }

            InventoryAssetMovement::create([
                'institution_id' => $asset->institution_id,
                'asset_id' => $asset->id,
                'item_id' => $asset->item_id,
                'movement_date' => $movementDate ?? now()->toDateString(),
                'from_room_id' => $fromRoomId,
                'to_room_id' => $toRoomId,
                'from_building_id' => $fromBuildingId,
                'to_building_id' => $room->building_id,
                'reference_number' => $referenceNumber,
                'notes' => $notes,
                'created_by' => $userId,
            ]);

            $asset->update([
                'room_id' => $toRoomId,
                'building_id' => $room->building_id,
                'location_start_date' => $movementDate ?? now()->toDateString(),
                'updated_by' => $userId,
            ]);

            return $asset->fresh(['item.category', 'room', 'building', 'responsibleEmployee']);
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildQrCardsForAssets(iterable $assets): array
    {
        $cards = [];
        foreach ($assets as $asset) {
            $cards[] = [
                'asset_id' => $asset->id,
                'asset_number' => $asset->asset_number,
                'inventory_number' => $asset->inventory_number,
                'serial_number' => $asset->serial_number,
                'item_name' => $asset->item?->name,
                'item_code' => $asset->item?->code,
                'room_name' => $asset->room?->name,
                'qr_code' => $this->generateQrImageBase64($asset),
            ];
        }

        return $cards;
    }

    public function syncItemQuantityFromAssets(?InventoryItem $item): void
    {
        if (! $item || $item->tracking_type !== InventoryCatalog::TRACKING_INDIVIDUAL) {
            return;
        }

        $activeCount = InventoryAsset::where('item_id', $item->id)
            ->active()
            ->count();

        $item->update(['quantity' => $activeCount]);
    }

    public function generateAssetNumber(int $institutionId): string
    {
        $year = date('Y');
        $prefix = "AST-{$year}-";

        $last = InventoryAsset::where('institution_id', $institutionId)
            ->where('asset_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('asset_number');

        $sequence = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $sequence = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    protected function buildInventoryNumber(InventoryItem $item, string $assetNumber): string
    {
        $base = $item->master_code ?? $item->code ?? $item->name;

        return $base . '/' . $assetNumber;
    }

    public function makeQrToken(int $assetId, int $institutionId): string
    {
        $payload = json_encode([
            'v' => 1,
            't' => 'inventory_asset',
            'id' => $assetId,
            'iid' => $institutionId,
        ], JSON_UNESCAPED_SLASHES);

        $payloadB64 = $this->b64urlEncode($payload);
        $signature = $this->b64urlEncode(hash_hmac('sha256', $payloadB64, $this->secret(), true));

        return self::QR_TOKEN_PREFIX . '.' . $payloadB64 . '.' . $signature;
    }

    /**
     * @return array{type: string, id: int, institution_id: int}|null
     */
    public function parseQrToken(string $token): ?array
    {
        $parts = explode('.', $token, 3);
        if (count($parts) !== 3 || $parts[0] !== self::QR_TOKEN_PREFIX) {
            return null;
        }

        [$prefix, $payloadB64, $signature] = $parts;
        unset($prefix);

        $expected = $this->b64urlEncode(hash_hmac('sha256', $payloadB64, $this->secret(), true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode($this->b64urlDecode($payloadB64), true);
        if (! is_array($payload) || ($payload['t'] ?? '') !== 'inventory_asset') {
            return null;
        }

        return [
            'type' => $payload['t'],
            'id' => (int) ($payload['id'] ?? 0),
            'institution_id' => (int) ($payload['iid'] ?? 0),
        ];
    }

    public function generateQrImageBase64(InventoryAsset $asset): string
    {
        $token = $asset->qr_token ?: $this->makeQrToken($asset->id, $asset->institution_id);
        if (! $asset->qr_token) {
            $asset->update(['qr_token' => $token]);
        }

        $svg = QrCode::format('svg')
            ->size(240)
            ->margin(2)
            ->errorCorrection('H')
            ->generate($token);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function resolveByQrToken(string $token, ?int $expectedInstitutionId = null): ?InventoryAsset
    {
        $parsed = $this->parseQrToken($token);
        if (! $parsed || $parsed['id'] < 1) {
            return null;
        }

        if ($expectedInstitutionId && $parsed['institution_id'] !== $expectedInstitutionId) {
            return null;
        }

        return InventoryAsset::with(['item.category', 'room', 'building', 'responsibleEmployee'])
            ->where('id', $parsed['id'])
            ->where('institution_id', $parsed['institution_id'])
            ->first();
    }

    protected function secret(): string
    {
        return (string) config('app.key');
    }

    protected function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    protected function b64urlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }
}
