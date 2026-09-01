<?php

namespace App\Services;

use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\Room;
use App\Support\InventoryCatalog;
use Illuminate\Support\Facades\DB;

class InventoryItemImportService
{
    public const HEADERS = [
        'kode_kategori',
        'nama',
        'tipe_pelacakan',
        'kode_barang',
        'merk',
        'model',
        'jumlah',
        'satuan',
        'kondisi',
        'status',
        'tanggal_beli',
        'harga_beli',
        'supplier',
        'sumber_dana',
        'cara_perolehan',
        'ruangan',
        'catatan_lokasi',
        'deskripsi',
    ];

    public function __construct(
        private InventoryService $inventoryService
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{success:int,failed:int,updated:int,errors:array<int,string>}
     */
    public function importFromRows(array $rows, int $institutionId, int $userId): array
    {
        $results = [
            'success' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        if ($rows === []) {
            throw new \Exception('Tidak ada data barang untuk diimpor.');
        }

        $categoriesByCode = InventoryCategory::query()
            ->where('institution_id', $institutionId)
            ->get()
            ->keyBy(fn ($c) => strtoupper(trim((string) $c->code)));

        $roomsByName = Room::query()
            ->whereHas('building', fn ($q) => $q->where('institution_id', $institutionId))
            ->get()
            ->keyBy(fn ($r) => mb_strtolower(trim((string) $r->name)));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            if (! is_array($row)) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNumber}: format baris tidak valid.";

                continue;
            }

            $data = $this->normalizeRowKeys($row);
            if ($this->isEmptyAssocRow($data)) {
                continue;
            }

            try {
                $outcome = $this->importRow($data, $institutionId, $userId, $categoriesByCode, $roomsByName);
                if ($outcome === 'created') {
                    $results['success']++;
                } elseif ($outcome === 'updated') {
                    $results['updated']++;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, InventoryCategory>  $categoriesByCode
     * @param  \Illuminate\Support\Collection<string, Room>  $roomsByName
     */
    private function importRow(array $row, int $institutionId, int $userId, $categoriesByCode, $roomsByName): string
    {
        $categoryCode = strtoupper(trim((string) ($row['kode_kategori'] ?? '')));
        $name = $this->clean($row['nama'] ?? null);

        if ($categoryCode === '') {
            throw new \Exception('kode_kategori wajib diisi.');
        }
        if ($name === null || $name === '') {
            throw new \Exception('nama wajib diisi.');
        }

        $category = $categoriesByCode->get($categoryCode);
        if (! $category) {
            throw new \Exception("kode_kategori '{$categoryCode}' tidak ditemukan. Buat kategori di Pengaturan terlebih dahulu.");
        }

        $trackingRaw = mb_strtolower(trim((string) ($row['tipe_pelacakan'] ?? 'stock')));
        $trackingType = in_array($trackingRaw, ['individual', 'aset', 'aset individual', 'unit'], true)
            ? InventoryCatalog::TRACKING_INDIVIDUAL
            : InventoryCatalog::TRACKING_STOCK;

        $condition = $this->clean($row['kondisi'] ?? null) ?: 'Baik';
        if (! in_array($condition, ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Habis Pakai'], true)) {
            throw new \Exception('kondisi tidak valid. Gunakan: Baik, Rusak Ringan, Rusak Berat, Habis Pakai.');
        }

        $quantity = $this->parseInt($row['jumlah'] ?? null) ?? 1;
        if ($quantity < 1 || $quantity > 500) {
            throw new \Exception('jumlah harus antara 1 dan 500.');
        }

        $status = $this->clean($row['status'] ?? null) ?: 'Tersedia';
        $roomName = mb_strtolower(trim((string) ($row['ruangan'] ?? '')));
        $room = $roomName !== '' ? $roomsByName->get($roomName) : null;
        if ($roomName !== '' && ! $room) {
            throw new \Exception("ruangan '{$row['ruangan']}' tidak ditemukan.");
        }

        $payload = [
            'institution_id' => $institutionId,
            'category_id' => $category->id,
            'tracking_type' => $trackingType,
            'name' => $name,
            'brand' => $this->clean($row['merk'] ?? null),
            'model' => $this->clean($row['model'] ?? null),
            'quantity' => $quantity,
            'unit' => $this->clean($row['satuan'] ?? null) ?: 'Unit',
            'condition' => $condition,
            'status' => $status,
            'purchase_date' => $this->parseDate($row['tanggal_beli'] ?? null),
            'purchase_price' => $this->parseDecimal($row['harga_beli'] ?? null),
            'supplier' => $this->clean($row['supplier'] ?? null),
            'funding_source' => $this->clean($row['sumber_dana'] ?? null),
            'acquisition_method' => $this->clean($row['cara_perolehan'] ?? null),
            'location_note' => $this->clean($row['catatan_lokasi'] ?? null),
            'description' => $this->clean($row['deskripsi'] ?? null),
            'room_id' => $room?->id,
            'building_id' => $room?->building_id,
        ];

        $code = $this->clean($row['kode_barang'] ?? null);
        if ($code) {
            $payload['code'] = $code;
        }

        $existing = null;
        if ($code) {
            $existing = InventoryItem::where('institution_id', $institutionId)
                ->where('code', $code)
                ->first();
        }

        if ($existing) {
            if ($existing->isIndividualTracked() || $trackingType === InventoryCatalog::TRACKING_INDIVIDUAL) {
                throw new \Exception('Barang dengan kode ini sudah ada. Update import hanya untuk master stok.');
            }

            unset($payload['quantity'], $payload['tracking_type'], $payload['institution_id'], $payload['category_id']);
            if (empty($payload['code'])) {
                unset($payload['code']);
            }
            $this->inventoryService->update($existing, array_filter($payload, fn ($v) => $v !== null), $userId);

            return 'updated';
        }

        $this->inventoryService->create($payload, $userId);

        return 'created';
    }

    /**
     * @return list<array<int, string|int|float|null>>
     */
    public function rowsForExport(iterable $items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $item->category?->code ?? '',
                $item->name ?? '',
                $item->tracking_type === InventoryCatalog::TRACKING_INDIVIDUAL ? 'individual' : 'stock',
                $item->code ?? '',
                $item->brand ?? '',
                $item->model ?? '',
                (int) ($item->quantity ?? 0),
                $item->unit ?? 'Unit',
                $item->condition ?? '',
                $item->status ?? '',
                $item->purchase_date?->format('Y-m-d') ?? '',
                $item->purchase_price !== null ? (float) $item->purchase_price : '',
                $item->supplier ?? '',
                $item->funding_source ?? '',
                $item->acquisition_method ?? '',
                $item->room?->name ?? '',
                $item->location_note ?? '',
                $item->description ?? '',
            ];
        }

        return $rows;
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $k = mb_strtolower(trim(preg_replace('/[\s-]+/', '_', (string) $key)));
            $normalized[$k] = $value;
        }

        return $normalized;
    }

    private function isEmptyAssocRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s === '' ? null : $s;
    }

    private function parseInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private function parseDecimal(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $s = preg_replace('/[^\d.,-]/', '', (string) $value);
        $s = str_replace('.', '', str_replace(',', '.', $s));

        return is_numeric($s) ? (float) $s : null;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        $s = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
            return $s;
        }
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $s, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        return null;
    }
}
