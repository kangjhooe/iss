<?php

namespace App\Services;

use App\Models\Student;
use App\Models\UksVisit;
use App\Models\UksVisitType;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UksService
{
    public const DEFAULT_VISIT_TYPES = [
        ['name' => 'Pemeriksaan rutin', 'code' => 'RUTIN', 'description' => 'Pemeriksaan kesehatan berkala'],
        ['name' => 'P3K / Pertolongan pertama', 'code' => 'P3K', 'description' => 'Pertolongan pertama di sekolah'],
        ['name' => 'Sakit di sekolah', 'code' => 'SAKIT', 'description' => 'Siswa sakit saat di sekolah'],
        ['name' => 'Screening kesehatan', 'code' => 'SCREEN', 'description' => 'Skrining kesehatan / antropometri'],
        ['name' => 'Imunisasi', 'code' => 'IMUN', 'description' => 'Kegiatan imunisasi'],
        ['name' => 'Rujukan', 'code' => 'RUJUK', 'description' => 'Dirujuk ke fasilitas kesehatan'],
    ];

    public function listForInstitution(int $institutionId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->baseListQuery($institutionId, $filters);

        return $query->paginate($perPage);
    }

    public function listForExport(int $institutionId, array $filters = [], int $limit = 5000): Collection
    {
        return $this->baseListQuery($institutionId, $filters)->limit($limit)->get();
    }

    public function listByStudent(int $studentId, int $institutionId): LengthAwarePaginator
    {
        return UksVisit::with([
            'visitType:id,name,code,description',
            'recorder:id,name',
        ])
            ->forInstitution($institutionId)
            ->forStudent($studentId)
            ->orderBy('visit_date', 'desc')
            ->paginate(20);
    }

    /**
     * Riwayat kunjungan UKS untuk portal siswa (tanpa paginasi berat).
     */
    public function listForStudentPortal(int $studentId, int $institutionId, int $limit = 100): Collection
    {
        return UksVisit::with([
            'visitType:id,name,code',
            'recorder:id,name',
        ])
            ->forInstitution($institutionId)
            ->forStudent($studentId)
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Ringkasan kunjungan UKS milik satu siswa.
     *
     * @return array{total:int,selesai:int,observasi:int,rujuk:int,last_visit_date:?string}
     */
    public function summaryForStudent(int $studentId, int $institutionId): array
    {
        $base = UksVisit::forInstitution($institutionId)->forStudent($studentId);

        $byStatus = (clone $base)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $last = (clone $base)->orderByDesc('visit_date')->orderByDesc('id')->value('visit_date');

        return [
            'total' => (int) (clone $base)->count(),
            'selesai' => (int) ($byStatus['selesai'] ?? 0),
            'observasi' => (int) ($byStatus['observasi'] ?? 0),
            'rujuk' => (int) ($byStatus['rujuk'] ?? 0),
            'last_visit_date' => $last ? Carbon::parse($last)->format('Y-m-d') : null,
        ];
    }

    public function create(int $institutionId, array $data, int $recordedBy): UksVisit
    {
        $student = Student::where('id', $data['student_id'])
            ->where('institution_id', $institutionId)
            ->firstOrFail();

        $typeId = $data['uks_visit_type_id'] ?? null;
        if ($typeId) {
            UksVisitType::where('id', $typeId)
                ->where('institution_id', $institutionId)
                ->where('is_active', true)
                ->firstOrFail();
        }

        $visit = UksVisit::create([
            'institution_id' => $institutionId,
            'student_id' => $student->id,
            'recorded_by' => $data['recorded_by'] ?? $recordedBy,
            'uks_visit_type_id' => $typeId,
            'visit_date' => $data['visit_date'],
            'status' => $data['status'] ?? 'selesai',
            'complaint' => $data['complaint'] ?? null,
            'action_taken' => $data['action_taken'] ?? null,
            'notes' => $data['notes'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'weight_kg' => $data['weight_kg'] ?? null,
            'blood_pressure' => $data['blood_pressure'] ?? null,
            'temperature_c' => $data['temperature_c'] ?? null,
            'academic_year_id' => $student->academic_year_id,
            'semester_id' => $student->semester_id,
            'class_id' => $student->class_id,
        ]);

        return $visit->load(['student.class:id,name', 'recorder', 'visitType']);
    }

    public function update(UksVisit $visit, array $data): UksVisit
    {
        if (isset($data['uks_visit_type_id']) && $data['uks_visit_type_id']) {
            UksVisitType::where('id', $data['uks_visit_type_id'])
                ->where('institution_id', $visit->institution_id)
                ->firstOrFail();
        }

        if (isset($data['student_id']) && (int) $data['student_id'] !== (int) $visit->student_id) {
            $student = Student::where('id', $data['student_id'])
                ->where('institution_id', $visit->institution_id)
                ->firstOrFail();
            $data['academic_year_id'] = $student->academic_year_id;
            $data['semester_id'] = $student->semester_id;
            $data['class_id'] = $student->class_id;
        }

        $visit->update($data);

        return $visit->fresh(['student.class:id,name', 'recorder', 'visitType']);
    }

    public function listTypes(int $institutionId, bool $activeOnly = true): Collection
    {
        $query = UksVisitType::forInstitution($institutionId)->orderBy('name');
        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    public function seedDefaultTypes(int $institutionId): Collection
    {
        foreach (self::DEFAULT_VISIT_TYPES as $row) {
            $exists = UksVisitType::where('institution_id', $institutionId)
                ->where(function ($q) use ($row) {
                    $q->where('code', $row['code'])->orWhere('name', $row['name']);
                })
                ->exists();
            if ($exists) {
                continue;
            }
            UksVisitType::create([
                'institution_id' => $institutionId,
                'name' => $row['name'],
                'code' => $row['code'],
                'description' => $row['description'],
                'is_active' => true,
            ]);
        }

        return $this->listTypes($institutionId, false);
    }

    public function getStats(int $institutionId, ?int $year = null): array
    {
        $year = $year ?? (int) Carbon::now()->format('Y');
        $start = "{$year}-01-01";
        $end = "{$year}-12-31";

        $base = UksVisit::forInstitution($institutionId)
            ->whereBetween('visit_date', [$start, $end]);

        $totalThisMonth = (clone $base)
            ->whereMonth('visit_date', Carbon::now()->month)
            ->whereYear('visit_date', $year)
            ->count();

        $byMonth = (clone $base)
            ->select(DB::raw('MONTH(visit_date) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('MONTH(visit_date)'))
            ->pluck('total', 'month');

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[] = ['month' => $m, 'total' => (int) ($byMonth[$m] ?? 0)];
        }

        $byType = UksVisit::forInstitution($institutionId)
            ->whereBetween('visit_date', [$start, $end])
            ->leftJoin('uks_visit_types', 'uks_visits.uks_visit_type_id', '=', 'uks_visit_types.id')
            ->select(
                DB::raw("COALESCE(uks_visit_types.name, 'Tanpa jenis') as type_name"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('uks_visit_types.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['type_name' => $r->type_name, 'total' => (int) $r->total])
            ->values()
            ->all();

        return [
            'year' => $year,
            'total_this_month' => $totalThisMonth,
            'total_year' => (clone $base)->count(),
            'by_month' => $months,
            'by_type' => $byType,
        ];
    }

    /**
     * Shape for Buku Induk section M.
     *
     * @return array<int, array{date: string, type: string, notes: string}>
     */
    public function healthRecordsForStudent(int $studentId): array
    {
        return UksVisit::with('visitType:id,name')
            ->where('student_id', $studentId)
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (UksVisit $v) {
                $parts = array_filter([
                    $v->complaint ? 'Keluhan: '.$v->complaint : null,
                    $v->action_taken ? 'Tindakan: '.$v->action_taken : null,
                    $v->notes,
                    $v->status && $v->status !== 'selesai' ? 'Status: '.$v->status : null,
                ]);

                return [
                    'date' => $v->visit_date?->format('Y-m-d'),
                    'type' => $v->visitType?->name ?? '-',
                    'notes' => $parts ? implode(' | ', $parts) : '-',
                ];
            })
            ->values()
            ->all();
    }

    protected function baseListQuery(int $institutionId, array $filters)
    {
        $query = UksVisit::with([
            'student' => fn ($q) => $q->select('id', 'name', 'nis', 'nisn', 'class_id')->with('class:id,name'),
            'recorder:id,name,email',
            'visitType:id,name,code,description',
        ])
            ->forInstitution($institutionId)
            ->orderBy('visit_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (!empty($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }
        if (!empty($filters['recorded_by'])) {
            $query->where('recorded_by', $filters['recorded_by']);
        }
        if (!empty($filters['uks_visit_type_id'])) {
            $query->where('uks_visit_type_id', $filters['uks_visit_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', $filters['class_id']);
        }
        if (!empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }
        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', $filters['semester_id']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
