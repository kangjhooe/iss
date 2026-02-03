<?php

namespace App\Services;

use App\Models\GuestVisit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GuestVisitService
{
    public function list(array $filters, ?int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = GuestVisit::query()
            ->with('creator')
            ->when($institutionId !== null, fn (Builder $q) => $q->where('institution_id', $institutionId));

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('nama_tamu', 'like', $term)
                    ->orWhere('instansi_asal', 'like', $term)
                    ->orWhere('tujuan_kunjungan', 'like', $term)
                    ->orWhere('orang_ditemui', 'like', $term)
                    ->orWhere('no_identitas', 'like', $term);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('waktu_masuk', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('waktu_masuk', '<=', $filters['date_to']);
        }

        $query->orderByDesc('waktu_masuk');

        return $query->paginate($perPage);
    }

    /**
     * List guest visits for export (no pagination, same filters).
     */
    public function listForExport(array $filters, ?int $institutionId, int $limit = 2000): Collection
    {
        $query = GuestVisit::query()
            ->when($institutionId !== null, fn (Builder $q) => $q->where('institution_id', $institutionId));

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('nama_tamu', 'like', $term)
                    ->orWhere('instansi_asal', 'like', $term)
                    ->orWhere('tujuan_kunjungan', 'like', $term)
                    ->orWhere('orang_ditemui', 'like', $term)
                    ->orWhere('no_identitas', 'like', $term);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('waktu_masuk', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('waktu_masuk', '<=', $filters['date_to']);
        }

        $query->orderByDesc('waktu_masuk');

        return $query->limit($limit)->get();
    }

    public function create(array $data, int $institutionId, int $userId, \Illuminate\Http\UploadedFile $file): GuestVisit
    {
        return DB::transaction(function () use ($data, $institutionId, $userId, $file) {
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $userId;
            $data['waktu_masuk'] = $data['waktu_masuk'] ?? now();

            $path = $this->storePhoto($file, $institutionId);
            $data['foto_path'] = $path;

            return GuestVisit::create($data);
        });
    }

    public function update(GuestVisit $visit, array $data, ?\Illuminate\Http\UploadedFile $file = null): GuestVisit
    {
        return DB::transaction(function () use ($visit, $data, $file) {
            if ($file) {
                if ($visit->foto_path && Storage::disk('public')->exists($visit->foto_path)) {
                    Storage::disk('public')->delete($visit->foto_path);
                }
                $data['foto_path'] = $this->storePhoto($file, $visit->institution_id);
            }

            $visit->update($data);
            return $visit->fresh('creator');
        });
    }

    public function setWaktuKeluar(GuestVisit $visit, $waktuKeluar = null): GuestVisit
    {
        $visit->update(['waktu_keluar' => $waktuKeluar ?? now()]);
        return $visit->fresh('creator');
    }

    public function delete(GuestVisit $visit): void
    {
        DB::transaction(function () use ($visit) {
            $visit->delete();
        });
    }

    protected function storePhoto(\Illuminate\Http\UploadedFile $file, int $institutionId): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $safeName = 'guest_' . time() . '_' . uniqid() . '.' . $extension;
        return $file->storeAs(
            'guest-visits/' . $institutionId,
            $safeName,
            'public'
        );
    }
}
