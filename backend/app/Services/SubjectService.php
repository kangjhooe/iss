<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\SubjectCatalog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class SubjectService
{
    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator|Collection
    {
        $query = Subject::forInstitution($institutionId)
            ->with(['catalog:id,code,name,jenjang'])
            ->orderBy('code');

        if (isset($filters['active_only']) && $filters['active_only']) {
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

        return $query->paginate($perPage);
    }

    public function create(int $institutionId, array $data): Subject
    {
        $data = $this->applyCatalogSource($institutionId, $data);
        $data['institution_id'] = $institutionId;
        $data['is_active'] = $data['is_active'] ?? true;

        return Subject::create($data);
    }

    public function update(Subject $subject, array $data): Subject
    {
        if (array_key_exists('subject_catalog_id', $data) && $data['subject_catalog_id']) {
            $data = $this->applyCatalogSource((int) $subject->institution_id, $data, $subject->id);
        }

        $subject->update($data);

        return $subject->fresh(['catalog:id,code,name,jenjang']);
    }

    /**
     * Isi code/name/description dari katalog jika subject_catalog_id dikirim.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyCatalogSource(int $institutionId, array $data, ?int $ignoreSubjectId = null): array
    {
        $catalogId = isset($data['subject_catalog_id']) ? (int) $data['subject_catalog_id'] : 0;
        if ($catalogId <= 0) {
            $data['subject_catalog_id'] = null;

            return $data;
        }

        $catalog = SubjectCatalog::query()->active()->find($catalogId);
        if (! $catalog) {
            throw new InvalidArgumentException('Mapel katalog tidak ditemukan atau nonaktif.');
        }

        $already = Subject::query()
            ->where('institution_id', $institutionId)
            ->where('subject_catalog_id', $catalog->id)
            ->when($ignoreSubjectId, fn ($q) => $q->where('id', '!=', $ignoreSubjectId))
            ->exists();
        if ($already) {
            throw new InvalidArgumentException('Mapel dari katalog ini sudah ditambahkan di sekolah Anda.');
        }

        $codeTaken = Subject::query()
            ->where('institution_id', $institutionId)
            ->where('code', $catalog->code)
            ->when($ignoreSubjectId, fn ($q) => $q->where('id', '!=', $ignoreSubjectId))
            ->exists();
        if ($codeTaken) {
            throw new InvalidArgumentException(
                'Kode katalog '.$catalog->code.' sudah dipakai mapel lain di sekolah ini. Hapus/ubah mapel tersebut dulu, atau pilih mapel katalog lain.'
            );
        }

        $data['subject_catalog_id'] = $catalog->id;
        $data['code'] = $catalog->code;
        $data['name'] = ! empty($data['name']) ? $data['name'] : $catalog->name;
        if (! array_key_exists('description', $data) || $data['description'] === null || $data['description'] === '') {
            $data['description'] = $catalog->description;
        }

        return $data;
    }
}
