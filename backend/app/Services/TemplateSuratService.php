<?php

namespace App\Services;

use App\Models\TemplateSurat;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TemplateSuratService
{
    public function list(array $filters, ?int $institutionId, int $perPage = 50): LengthAwarePaginator
    {
        $query = TemplateSurat::query();

        if (!empty($filters['scope']) && $filters['scope'] === 'platform') {
            $query->platform();
        } elseif (!empty($filters['scope']) && $filters['scope'] === 'own' && $institutionId) {
            $query->where('institution_id', $institutionId);
        } else {
            $query->forInstitution($institutionId);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        // Platform dulu, lalu template sekolah, lalu urut nama
        $query->orderByRaw('CASE WHEN institution_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('nama');

        return $query->paginate($perPage);
    }

    public function find(int $id): ?TemplateSurat
    {
        return TemplateSurat::find($id);
    }

    public function create(array $data): TemplateSurat
    {
        return TemplateSurat::create($data);
    }

    public function update(TemplateSurat $template, array $data): TemplateSurat
    {
        $template->update($data);
        return $template->fresh();
    }

    public function delete(TemplateSurat $template): bool
    {
        return $template->delete();
    }

    public function toggleStatus(TemplateSurat $template): TemplateSurat
    {
        $template->update([
            'status' => $template->status === 'aktif' ? 'nonaktif' : 'aktif',
        ]);

        return $template->fresh();
    }

    /**
     * Salin template platform (atau template lain yang boleh dibaca) menjadi milik institusi.
     */
    public function fork(TemplateSurat $source, int $institutionId, ?string $kode = null): TemplateSurat
    {
        if (!$institutionId) {
            throw new InvalidArgumentException('Institusi wajib untuk menyalin template.');
        }

        $baseKode = strtoupper(trim($kode ?: $source->kode));
        $uniqueKode = $this->uniqueKodeForInstitution($baseKode, $institutionId);

        return TemplateSurat::create([
            'institution_id' => $institutionId,
            'source_template_id' => $source->id,
            'nama' => $source->nama,
            'kode' => $uniqueKode,
            'isi_html' => $source->isi_html,
            'status' => 'aktif',
            'letter_type_code' => $source->letter_type_code ?? '09',
            'subject_type' => $source->subject_type ?? 'siswa',
        ]);
    }

    private function uniqueKodeForInstitution(string $baseKode, int $institutionId): string
    {
        $baseKode = Str::limit($baseKode, 40, '');
        $kode = $baseKode;
        $i = 1;

        while (
            TemplateSurat::where('institution_id', $institutionId)
                ->where('kode', $kode)
                ->exists()
        ) {
            $suffix = '-C' . $i;
            $kode = Str::limit($baseKode, 50 - strlen($suffix), '') . $suffix;
            $i++;
            if ($i > 50) {
                $kode = Str::limit($baseKode, 30, '') . '-' . Str::upper(Str::random(6));
                break;
            }
        }

        return $kode;
    }
}
