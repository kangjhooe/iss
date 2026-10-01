<?php

namespace App\Services;

use App\Models\SubjectCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SubjectCatalogService
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator|Collection
    {
        $query = SubjectCatalog::query()
            ->orderBy('jenjang')
            ->orderBy('sort_order')
            ->orderBy('code');

        if (!empty($filters['jenjang'])) {
            $query->forJenjang($filters['jenjang']);
        }

        if (isset($filters['active_only']) && filter_var($filters['active_only'], FILTER_VALIDATE_BOOLEAN)) {
            $query->active();
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['per_page']) && $filters['per_page'] === 'all') {
            return $query->get();
        }

        return $query->paginate(min($perPage, 100));
    }

    public function find(int $id): SubjectCatalog
    {
        return SubjectCatalog::findOrFail($id);
    }

    public function create(array $data): SubjectCatalog
    {
        $this->assertCodeValid($data['code'], $data['jenjang']);
        $this->assertCodeUnique($data['code']);

        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $item = SubjectCatalog::create($data);

        Log::info('Subject catalog created', [
            'subject_catalog_id' => $item->id,
            'code' => $item->code,
            'jenjang' => $item->jenjang,
        ]);

        return $item;
    }

    public function update(SubjectCatalog $item, array $data): SubjectCatalog
    {
        $code = $data['code'] ?? $item->code;
        $jenjang = $data['jenjang'] ?? $item->jenjang;

        $this->assertCodeValid($code, $jenjang);
        $this->assertCodeUnique($code, $item->id);

        $item->update($data);

        Log::info('Subject catalog updated', [
            'subject_catalog_id' => $item->id,
        ]);

        return $item->fresh();
    }

    public function delete(SubjectCatalog $item): bool
    {
        $id = $item->id;
        $result = (bool) $item->delete();

        Log::info('Subject catalog deleted', [
            'subject_catalog_id' => $id,
        ]);

        return $result;
    }

    protected function assertCodeValid(string $code, string $jenjang): void
    {
        if (!preg_match('/^\d{4}$/', $code)) {
            throw ValidationException::withMessages([
                'code' => 'Kode harus 4 digit angka (contoh: 1001, 2002).',
            ]);
        }

        if (!SubjectCatalog::codeMatchesJenjang($code, $jenjang)) {
            $prefix = SubjectCatalog::expectedPrefixForJenjang($jenjang);
            $label = SubjectCatalog::JENJANG_LABELS[$jenjang] ?? $jenjang;
            throw ValidationException::withMessages([
                'code' => "Kode untuk jenjang {$label} harus diawali digit {$prefix} (contoh: {$prefix}001).",
            ]);
        }
    }

    protected function assertCodeUnique(string $code, ?int $excludeId = null): void
    {
        $query = SubjectCatalog::query()->where('code', $code);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'code' => 'Kode mata pelajaran ini sudah dipakai.',
            ]);
        }
    }
}
