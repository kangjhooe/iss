<?php

namespace App\Services;

use App\Models\DocumentPickup;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class DocumentPickupService
{
    public function __construct(
        protected StudentService $studentService
    ) {}

    public function list(array $filters, ?int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = DocumentPickup::query()
            ->with(['student:id,institution_id,nis,nisn,name,graduation_year,class_id', 'student.class:id,name', 'creator'])
            ->when($institutionId !== null, fn (Builder $q) => $q->where('institution_id', $institutionId));

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->whereHas('student', function (Builder $q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('nis', 'like', $term)
                    ->orWhere('nisn', 'like', $term);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('pickup_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('pickup_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['student_id'])) {
            $query->where('student_id', (int) $filters['student_id']);
        }

        $query->orderByDesc('pickup_date')->orderByDesc('id');

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  \Illuminate\Http\UploadedFile|null  $file
     */
    public function create(array $data, int $institutionId, int $userId, $file = null): DocumentPickup
    {
        $student = Student::find($data['student_id']);
        if (!$student) {
            throw new InvalidArgumentException('Siswa tidak ditemukan.');
        }
        if ($student->status !== 'Lulus') {
            throw new InvalidArgumentException('Hanya siswa dengan status Lulus (alumni) yang dapat dicatat pengambilan ijazahnya.');
        }
        if ((int) $student->institution_id !== $institutionId) {
            throw new InvalidArgumentException('Siswa tidak termasuk dalam institusi ini.');
        }

        return DB::transaction(function () use ($data, $institutionId, $userId, $file) {
            $data['institution_id'] = $institutionId;
            $data['created_by'] = $userId;
            $data['taken_ijazah'] = !empty($data['taken_ijazah']);
            $data['taken_raport'] = !empty($data['taken_raport']);
            $data['taken_skhun'] = !empty($data['taken_skhun']);

            if ($file) {
                $data['photo_path'] = $this->storePhoto($file, $institutionId);
            }

            return DocumentPickup::create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  \Illuminate\Http\UploadedFile|null  $file
     */
    public function update(DocumentPickup $pickup, array $data, $file = null): DocumentPickup
    {
        return DB::transaction(function () use ($pickup, $data, $file) {
            $data['taken_ijazah'] = isset($data['taken_ijazah']) ? (bool) $data['taken_ijazah'] : $pickup->taken_ijazah;
            $data['taken_raport'] = isset($data['taken_raport']) ? (bool) $data['taken_raport'] : $pickup->taken_raport;
            $data['taken_skhun'] = isset($data['taken_skhun']) ? (bool) $data['taken_skhun'] : $pickup->taken_skhun;

            if ($file) {
                if ($pickup->photo_path && Storage::disk('public')->exists($pickup->photo_path)) {
                    Storage::disk('public')->delete($pickup->photo_path);
                }
                $data['photo_path'] = $this->storePhoto($file, $pickup->institution_id);
            }

            $pickup->update($data);
            return $pickup->fresh(['student', 'student.class', 'creator']);
        });
    }

    public function delete(DocumentPickup $pickup): void
    {
        DB::transaction(function () use ($pickup) {
            if ($pickup->photo_path && Storage::disk('public')->exists($pickup->photo_path)) {
                Storage::disk('public')->delete($pickup->photo_path);
            }
            $pickup->delete();
        });
    }

    protected function storePhoto(\Illuminate\Http\UploadedFile $file, int $institutionId): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $safeName = 'pickup_' . time() . '_' . uniqid() . '.' . $extension;
        return $file->storeAs(
            'document-pickups/' . $institutionId,
            $safeName,
            'public'
        );
    }
}
