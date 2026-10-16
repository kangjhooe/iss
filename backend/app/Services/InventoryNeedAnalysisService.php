<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\InventoryAsset;
use App\Models\InventoryCategory;
use App\Models\InventoryItem;
use App\Models\InventoryNeedAnalysis;
use App\Models\InventoryNeedLine;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Support\InventoryCatalog;
use Illuminate\Support\Arr;

class InventoryNeedAnalysisService
{
    public const ROOM_TYPES = [
        'Kelas',
        'Laboratorium',
        'Perpustakaan',
        'Kantor',
        'Aula',
        'Musholla',
        'Kantin',
        'Toilet',
        'Gudang',
        'Lainnya',
    ];

    public function create(int $institutionId, array $data, int $userId): InventoryNeedAnalysis
    {
        $year = AcademicYear::query()->findOrFail($data['academic_year_id']);
        $title = trim((string) ($data['title'] ?? ''));
        $source = $this->sourceAnalysis($institutionId, $data['source_analysis_id'] ?? null);
        $withTemplate = $source ? false : (bool) ($data['with_template'] ?? true);

        $analysis = InventoryNeedAnalysis::create([
            'institution_id' => $institutionId,
            'academic_year_id' => $year->id,
            'academic_year_label' => $year->name,
            'title' => $title !== '' ? $title : 'Analisis Kebutuhan Sarana Prasarana '.$year->name,
            'status' => 'draft',
            'notes' => $source
                ? $source->notes
                : ($withTemplate
                    ? 'Template awal memakai rasio umum (1 ruang kelas per rombel, 1 meja dan 1 kursi per siswa). Sesuaikan rasio, harga, dan kaitan barang dengan kondisi sekolah sebelum dokumen difinalkan.'
                    : null),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        if ($source) {
            $this->copyLines($analysis, $source);
        } elseif ($withTemplate) {
            $this->seedTemplate($analysis);
        }

        return $this->refreshCounts($analysis);
    }

    public function update(InventoryNeedAnalysis $analysis, array $data, int $userId): InventoryNeedAnalysis
    {
        $this->assertDraft($analysis);

        if (array_key_exists('title', $data)) {
            $analysis->title = trim((string) $data['title']);
        }
        if (array_key_exists('notes', $data)) {
            $analysis->notes = $data['notes'] !== null ? trim((string) $data['notes']) : null;
        }
        if (! empty($data['academic_year_id']) && (int) $data['academic_year_id'] !== (int) $analysis->academic_year_id) {
            $year = AcademicYear::query()->findOrFail($data['academic_year_id']);
            $analysis->academic_year_id = $year->id;
            $analysis->academic_year_label = $year->name;
        }
        $analysis->updated_by = $userId;
        $analysis->save();

        return $this->refreshCounts($analysis);
    }

    public function addLine(InventoryNeedAnalysis $analysis, array $data, int $userId): InventoryNeedAnalysis
    {
        $this->assertDraft($analysis);
        $payload = $this->lineAttributes($analysis, $data);
        $maxSort = $analysis->lines()->where('section', $payload['section'])->max('sort_order');
        $payload['sort_order'] = ($maxSort === null ? 0 : (int) $maxSort) + 10;

        InventoryNeedLine::create($payload);
        $analysis->updated_by = $userId;

        return $this->refreshCounts($analysis);
    }

    public function updateLine(InventoryNeedAnalysis $analysis, InventoryNeedLine $line, array $data, int $userId): InventoryNeedAnalysis
    {
        $this->assertDraft($analysis);
        $this->assertLineBelongs($analysis, $line);

        $payload = $this->lineAttributes($analysis, $data);
        unset($payload['sort_order']);
        $line->fill($payload);
        $line->save();
        $analysis->updated_by = $userId;

        return $this->refreshCounts($analysis);
    }

    public function deleteLine(InventoryNeedAnalysis $analysis, InventoryNeedLine $line, int $userId): InventoryNeedAnalysis
    {
        $this->assertDraft($analysis);
        $this->assertLineBelongs($analysis, $line);
        $line->delete();
        $analysis->updated_by = $userId;

        return $this->refreshCounts($analysis);
    }

    public function finalize(InventoryNeedAnalysis $analysis, int $userId): InventoryNeedAnalysis
    {
        if ($analysis->status === 'final') {
            throw new \InvalidArgumentException('Dokumen sudah final.');
        }
        if ($analysis->lines()->count() === 0) {
            throw new \InvalidArgumentException('Tambahkan minimal satu baris sebelum final.');
        }

        $analysis = $this->refreshCounts($analysis);
        $analysis->status = 'final';
        $analysis->finalized_at = now();
        $analysis->updated_by = $userId;
        $analysis->save();

        return $analysis->load(['lines.category', 'academicYear']);
    }

    public function reopen(InventoryNeedAnalysis $analysis, int $userId): InventoryNeedAnalysis
    {
        if ($analysis->isDraft()) {
            throw new \InvalidArgumentException('Dokumen masih draf.');
        }

        $analysis->status = 'draft';
        $analysis->finalized_at = null;
        $analysis->updated_by = $userId;
        $analysis->save();

        return $this->refreshCounts($analysis);
    }

    /**
     * Hitung ulang dasar, jumlah tersedia, kekurangan, dan anggaran.
     * Dokumen final memakai angka yang sudah dikunci.
     */
    public function refreshCounts(InventoryNeedAnalysis $analysis): InventoryNeedAnalysis
    {
        $analysis->load('academicYear');

        if ($analysis->status === 'final') {
            return $analysis->load(['lines.category', 'academicYear']);
        }

        $context = $this->buildContext($analysis);
        $analysis->academic_year_label = $analysis->academicYear?->name ?: $analysis->academic_year_label;

        $lines = $analysis->lines()->get();
        foreach ($lines as $line) {
            $line->fill($this->computedAttributes($line, $context));
            if ($line->isDirty()) {
                $line->save();
            }
        }

        $freshLines = $analysis->lines()->get();
        $analysis->student_count = $context['student_count'];
        $analysis->rombel_count = $context['rombel_count'];
        $analysis->shortage_lines = $freshLines->where('shortage_qty', '>', 0)->count();
        $analysis->high_priority_lines = $freshLines
            ->where('priority', 'tinggi')
            ->where('shortage_qty', '>', 0)
            ->count();
        $procureBudget = (float) $freshLines->sum(fn ($line) => (float) ($line->procure_cost ?? 0));
        $repairBudget = (float) $freshLines->sum(fn ($line) => (float) ($line->repair_cost ?? 0));
        $analysis->procure_budget = number_format($procureBudget, 2, '.', '');
        $analysis->repair_budget = number_format($repairBudget, 2, '.', '');
        $analysis->estimated_budget = number_format($procureBudget + $repairBudget, 2, '.', '');

        if ($analysis->isDirty()) {
            $analysis->counts_synced_at = now();
            $analysis->save();
        }

        return $analysis->load(['lines.category', 'academicYear']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function lineAttributes(InventoryNeedAnalysis $analysis, array $data): array
    {
        $section = $data['section'];
        $basisType = $data['basis_type'];
        $itemIds = $this->normalizeItemIds($analysis->institution_id, Arr::wrap($data['source_item_ids'] ?? []));
        $categoryId = $section === 'sarana' && ! empty($data['category_id'])
            ? (int) $data['category_id']
            : null;

        if ($categoryId) {
            $ownsCategory = InventoryCategory::query()
                ->where('id', $categoryId)
                ->where('institution_id', $analysis->institution_id)
                ->exists();
            if (! $ownsCategory) {
                throw new \InvalidArgumentException('Kategori tidak termasuk institusi ini.');
            }
        }

        return [
            'analysis_id' => $analysis->id,
            'institution_id' => $analysis->institution_id,
            'section' => $section,
            'name' => trim((string) $data['name']),
            'unit' => trim((string) ($data['unit'] ?? 'Unit')) ?: 'Unit',
            'basis_type' => $basisType,
            'basis_room_type' => $basisType === 'ruang' ? ($data['basis_room_type'] ?? null) : null,
            'room_type' => $section === 'prasarana' ? ($data['room_type'] ?? null) : null,
            'basis_value' => $basisType === 'manual' ? (int) ($data['basis_value'] ?? 0) : 0,
            'ratio_numerator' => (int) $data['ratio_numerator'],
            'ratio_denominator' => (int) $data['ratio_denominator'],
            'usable_policy' => $data['usable_policy'] ?? 'baik_dan_ringan',
            'category_id' => $categoryId,
            'source_item_ids' => $section === 'sarana' && $itemIds !== [] ? $itemIds : null,
            'priority' => $data['priority'] ?? 'sedang',
            'unit_price' => $this->nullablePrice($data['unit_price'] ?? null),
            'unit_repair_price' => $this->nullablePrice($data['unit_repair_price'] ?? null),
            'notes' => isset($data['notes']) && trim((string) $data['notes']) !== ''
                ? trim((string) $data['notes'])
                : null,
            'sort_order' => 0,
        ];
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function normalizeItemIds(int $institutionId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        $found = InventoryItem::query()
            ->where('institution_id', $institutionId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($found) !== count($ids)) {
            throw new \InvalidArgumentException('Ada barang yang tidak termasuk institusi ini.');
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(InventoryNeedAnalysis $analysis): array
    {
        $institutionId = (int) $analysis->institution_id;
        $yearId = $analysis->academic_year_id ? (int) $analysis->academic_year_id : null;
        $year = $analysis->academicYear;

        $studentCount = 0;
        $rombelCount = 0;
        if ($yearId) {
            $studentCount = (int) Student::query()
                ->where('institution_id', $institutionId)
                ->where('status', Student::STATUS_ACTIVE)
                ->where('academic_year_id', $yearId)
                ->count();

            $rombelCount = (int) SchoolClass::query()
                ->where('institution_id', $institutionId)
                ->where('status', 'Aktif')
                ->where(function ($query) use ($yearId, $year) {
                    $query->where('academic_year_id', $yearId);
                    $names = array_values(array_filter(array_unique([
                        $year?->name,
                        $year?->code,
                    ])));
                    if ($names !== []) {
                        $query->orWhere(function ($inner) use ($names) {
                            $inner->whereNull('academic_year_id')->whereIn('academic_year', $names);
                        });
                    }
                })
                ->count();
        }

        $roomIndex = [];
        $roomRows = Room::query()
            ->where('institution_id', $institutionId)
            ->select('type', 'condition')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('type', 'condition')
            ->get();
        foreach ($roomRows as $row) {
            $type = (string) $row->type;
            $roomIndex[$type] ??= $this->emptyBuckets();
            $this->addBucket($roomIndex[$type], $row->condition, (int) $row->total);
        }

        $items = InventoryItem::query()
            ->where('institution_id', $institutionId)
            ->whereNull('disposed_at')
            ->whereNotIn('status', ['Hilang', 'Dijual'])
            ->get(['id', 'category_id', 'tracking_type', 'condition', 'quantity']);

        $assetRows = InventoryAsset::query()
            ->where('institution_id', $institutionId)
            ->where('disposal_status', InventoryCatalog::DISPOSAL_ACTIVE)
            ->whereNull('disposed_at')
            ->whereNotIn('status', ['Hilang', 'Dijual'])
            ->select('item_id', 'condition')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('item_id', 'condition')
            ->get();

        $assetsByItem = [];
        foreach ($assetRows as $row) {
            $assetsByItem[(int) $row->item_id][(string) $row->condition] = (int) $row->total;
        }

        $itemsWithAssets = InventoryAsset::query()
            ->where('institution_id', $institutionId)
            ->distinct()
            ->pluck('item_id')
            ->map(fn ($id) => (int) $id)
            ->flip();

        $itemIndex = [];
        $categoryIndex = [];
        foreach ($items as $item) {
            $itemId = (int) $item->id;
            $itemIndex[$itemId] = $item;
            if ($item->category_id) {
                $categoryIndex[(int) $item->category_id][] = $itemId;
            }
        }

        return [
            'student_count' => $studentCount,
            'rombel_count' => $rombelCount,
            'rooms' => $roomIndex,
            'items' => $itemIndex,
            'categories' => $categoryIndex,
            'assets' => $assetsByItem,
            'items_with_assets' => $itemsWithAssets,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, int|float|null>
     */
    private function computedAttributes(InventoryNeedLine $line, array $context): array
    {
        $basis = match ($line->basis_type) {
            'siswa' => (int) $context['student_count'],
            'rombel' => (int) $context['rombel_count'],
            'ruang' => $this->bucketTotal($context['rooms'][$line->basis_room_type] ?? $this->emptyBuckets()),
            default => (int) $line->basis_value,
        };

        $buckets = $line->section === 'prasarana'
            ? ($context['rooms'][$line->room_type] ?? $this->emptyBuckets())
            : $this->itemBuckets($line, $context);

        $required = $this->requiredQty($basis, (int) $line->ratio_numerator, (int) $line->ratio_denominator);
        $usable = $this->usableQty($buckets, (string) $line->usable_policy);
        $shortage = max(0, $required - $usable);
        $surplus = max(0, $usable - $required);
        $split = self::actionSplit(
            $shortage,
            (string) $line->usable_policy,
            (int) $buckets['rusak_ringan'],
            (int) $buckets['rusak_sedang'],
            (int) $buckets['rusak_berat']
        );
        $procureCost = $this->pricedCost($line->unit_price, $split['procure_qty']);
        $repairCost = $this->pricedCost($line->unit_repair_price, $split['repair_qty']);
        $knownCosts = array_values(array_filter([$procureCost, $repairCost], fn ($value) => $value !== null));

        return [
            'basis_value' => $basis,
            'existing_baik' => $buckets['baik'],
            'existing_rusak_ringan' => $buckets['rusak_ringan'],
            'existing_rusak_sedang' => $buckets['rusak_sedang'],
            'existing_rusak_berat' => $buckets['rusak_berat'],
            'existing_habis' => $buckets['habis'],
            'required_qty' => $required,
            'usable_qty' => $usable,
            'shortage_qty' => $shortage,
            'procure_qty' => $split['procure_qty'],
            'repair_qty' => $split['repair_qty'],
            'surplus_qty' => $surplus,
            'procure_cost' => $procureCost,
            'repair_cost' => $repairCost,
            'estimated_cost' => $knownCosts === []
                ? null
                : number_format(array_sum(array_map('floatval', $knownCosts)), 2, '.', ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, int>
     */
    private function itemBuckets(InventoryNeedLine $line, array $context): array
    {
        $ids = array_values(array_filter(array_map('intval', $line->source_item_ids ?? [])));
        if ($ids === [] && $line->category_id) {
            $ids = $context['categories'][(int) $line->category_id] ?? [];
        }

        $buckets = $this->emptyBuckets();
        foreach ($ids as $itemId) {
            $item = $context['items'][$itemId] ?? null;
            if (! $item) {
                continue;
            }
            $isIndividual = ($item->tracking_type ?: InventoryCatalog::TRACKING_STOCK) === InventoryCatalog::TRACKING_INDIVIDUAL;
            if ($isIndividual && isset($context['items_with_assets'][$itemId])) {
                foreach ($context['assets'][$itemId] ?? [] as $condition => $qty) {
                    $this->addBucket($buckets, (string) $condition, (int) $qty);
                }
                continue;
            }
            $this->addBucket($buckets, $item->condition, (int) $item->quantity);
        }

        return $buckets;
    }

    private function requiredQty(int $basis, int $numerator, int $denominator): int
    {
        if ($basis <= 0 || $numerator <= 0 || $denominator <= 0) {
            return 0;
        }

        return intdiv(($basis * $numerator) + $denominator - 1, $denominator);
    }

    /**
     * @param  array<string, int>  $buckets
     */
    private function usableQty(array $buckets, string $policy): int
    {
        $qty = (int) ($buckets['baik'] ?? 0);
        if ($policy === 'baik_dan_ringan') {
            $qty += (int) ($buckets['rusak_ringan'] ?? 0);
        }

        return $qty;
    }

    /**
     * @param  array<string, int>  $buckets
     */
    private function bucketTotal(array $buckets): int
    {
        return array_sum($buckets);
    }

    /**
     * @return array<string, int>
     */
    private function emptyBuckets(): array
    {
        return [
            'baik' => 0,
            'rusak_ringan' => 0,
            'rusak_sedang' => 0,
            'rusak_berat' => 0,
            'habis' => 0,
        ];
    }

    /**
     * @param  array<string, int>  $buckets
     */
    private function addBucket(array &$buckets, ?string $condition, int $qty): void
    {
        if ($qty <= 0) {
            return;
        }
        $key = match ($condition) {
            'Baik' => 'baik',
            'Rusak Ringan' => 'rusak_ringan',
            'Rusak Sedang' => 'rusak_sedang',
            'Rusak Berat' => 'rusak_berat',
            'Habis Pakai' => 'habis',
            default => null,
        };
        if ($key) {
            $buckets[$key] += $qty;
        }
    }

    private function seedTemplate(InventoryNeedAnalysis $analysis): void
    {
        $rows = [
            ['prasarana', 'Ruang Kelas', 'Ruang', 'rombel', null, 'Kelas', 0, 1, 1, null],
            ['prasarana', 'Laboratorium', 'Ruang', 'manual', null, 'Laboratorium', 1, 1, 1, 'Sesuaikan jumlah dengan jenis lab yang dipakai.'],
            ['prasarana', 'Perpustakaan', 'Ruang', 'manual', null, 'Perpustakaan', 1, 1, 1, null],
            ['prasarana', 'Ruang Kantor', 'Ruang', 'manual', null, 'Kantor', 1, 1, 1, null],
            ['prasarana', 'Toilet', 'Ruang', 'siswa', null, 'Toilet', 0, 1, 30, 'Contoh rasio 1 ruang per 30 siswa. Ubah sesuai standar sekolah.'],
            ['sarana', 'Meja Siswa', 'Unit', 'siswa', null, null, 0, 1, 1, 'Kaitkan ke barang atau kategori meja siswa agar jumlah tersedia terisi.'],
            ['sarana', 'Kursi Siswa', 'Unit', 'siswa', null, null, 0, 1, 1, 'Kaitkan ke barang atau kategori kursi siswa agar jumlah tersedia terisi.'],
            ['sarana', 'Meja Guru', 'Unit', 'rombel', null, null, 0, 1, 1, null],
            ['sarana', 'Kursi Guru', 'Unit', 'rombel', null, null, 0, 1, 1, null],
            ['sarana', 'Papan Tulis', 'Unit', 'ruang', 'Kelas', null, 0, 1, 1, null],
        ];

        foreach ($rows as $index => $row) {
            InventoryNeedLine::create([
                'analysis_id' => $analysis->id,
                'institution_id' => $analysis->institution_id,
                'section' => $row[0],
                'name' => $row[1],
                'unit' => $row[2],
                'basis_type' => $row[3],
                'basis_room_type' => $row[4],
                'room_type' => $row[5],
                'basis_value' => $row[6],
                'ratio_numerator' => $row[7],
                'ratio_denominator' => $row[8],
                'usable_policy' => 'baik_dan_ringan',
                'priority' => 'sedang',
                'notes' => $row[9],
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    /**
     * @return array{procure_qty: int, repair_qty: int}
     */
    public static function actionSplit(int $shortage, string $policy, int $ringan, int $sedang, int $berat): array
    {
        $repairable = max(0, $sedang) + max(0, $berat);
        if ($policy === 'baik_only') {
            $repairable += max(0, $ringan);
        }
        $gap = max(0, $shortage);
        $repair = min($repairable, $gap);

        return [
            'repair_qty' => $repair,
            'procure_qty' => $gap - $repair,
        ];
    }

    private function sourceAnalysis(int $institutionId, mixed $sourceId): ?InventoryNeedAnalysis
    {
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        $source = InventoryNeedAnalysis::query()
            ->where('institution_id', $institutionId)
            ->with('lines')
            ->find((int) $sourceId);

        if (! $source) {
            throw new \InvalidArgumentException('Dokumen sumber tidak ditemukan.');
        }

        return $source;
    }

    private function copyLines(InventoryNeedAnalysis $analysis, InventoryNeedAnalysis $source): void
    {
        $validIds = array_flip(InventoryItem::query()
            ->where('institution_id', $analysis->institution_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all());

        foreach ($source->lines as $line) {
            $itemIds = array_values(array_filter(
                array_map('intval', $line->source_item_ids ?? []),
                fn ($id) => isset($validIds[$id])
            ));

            InventoryNeedLine::create([
                'analysis_id' => $analysis->id,
                'institution_id' => $analysis->institution_id,
                'section' => $line->section,
                'name' => $line->name,
                'unit' => $line->unit,
                'basis_type' => $line->basis_type,
                'basis_room_type' => $line->basis_room_type,
                'room_type' => $line->room_type,
                'basis_value' => $line->basis_type === 'manual' ? (int) $line->basis_value : 0,
                'ratio_numerator' => (int) $line->ratio_numerator,
                'ratio_denominator' => (int) $line->ratio_denominator,
                'usable_policy' => $line->usable_policy,
                'category_id' => $line->category_id,
                'source_item_ids' => $itemIds === [] ? null : $itemIds,
                'priority' => $line->priority,
                'unit_price' => $line->unit_price,
                'unit_repair_price' => $line->unit_repair_price,
                'notes' => $line->notes,
                'sort_order' => (int) $line->sort_order,
            ]);
        }
    }

    private function nullablePrice(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function pricedCost(mixed $unitPrice, int $qty): ?string
    {
        if ($unitPrice === null || $unitPrice === '') {
            return null;
        }

        return number_format(((float) $unitPrice) * $qty, 2, '.', '');
    }

    private function assertDraft(InventoryNeedAnalysis $analysis): void
    {
        if ($analysis->status === 'final') {
            throw new \InvalidArgumentException('Dokumen final tidak bisa diubah. Buka kembali dulu jika perlu revisi.');
        }
    }

    private function assertLineBelongs(InventoryNeedAnalysis $analysis, InventoryNeedLine $line): void
    {
        if ((int) $line->analysis_id !== (int) $analysis->id) {
            throw new \InvalidArgumentException('Baris tidak termasuk dokumen ini.');
        }
    }
}
