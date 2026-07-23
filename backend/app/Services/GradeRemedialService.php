<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\GradeRemedial;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\SubjectKkm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GradeRemedialService
{
    public function __construct(
        private GradeService $gradeService
    ) {
    }

    /**
     * Students below KKM (nilai_akhir) for a class+subject+semester.
     *
     * @return array{kkm: float|null, students: array<int, array>, meta: array}
     */
    public function belowKkm(
        int $institutionId,
        int $classId,
        int $subjectId,
        int $semesterId
    ): array {
        $schoolClass = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->first(['id', 'name', 'grade']);

        $gradeLevel = (int) ($schoolClass?->grade ?? 0);
        $kkm = null;
        if ($gradeLevel > 0) {
            $kkmRow = SubjectKkm::query()
                ->where('institution_id', $institutionId)
                ->where('subject_id', $subjectId)
                ->where('grade', $gradeLevel)
                ->where('semester_id', $semesterId)
                ->first();
            $kkm = $kkmRow?->kkm !== null ? (float) $kkmRow->kkm : null;
        }

        $students = Student::query()
            ->where('class_id', $classId)
            ->where('institution_id', $institutionId)
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'nisn']);

        $scores = $this->gradeService->nilaiAkhirScoresForClass(
            $institutionId,
            $classId,
            $subjectId,
            $semesterId
        );

        $activeRemedials = GradeRemedial::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->whereIn('status', [GradeRemedial::STATUS_PLANNED, GradeRemedial::STATUS_COMPLETED])
            ->orderByDesc('id')
            ->get()
            ->groupBy('student_id');

        $rows = [];
        foreach ($students as $student) {
            $sid = (int) $student->id;
            $score = $scores[$sid] ?? null;
            $isTuntas = SubjectKkm::isTuntas($score !== null ? (float) $score : null, $kkm);
            if ($isTuntas !== false) {
                continue;
            }

            $latest = $activeRemedials->get($sid)?->first();

            $rows[] = [
                'student_id' => $sid,
                'student' => [
                    'id' => $sid,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                ],
                'nilai_akhir' => $score,
                'kkm' => $kkm,
                'gap' => $kkm !== null && $score !== null ? round((float) $kkm - (float) $score, 2) : null,
                'latest_remedial' => $latest ? $this->toArray($latest) : null,
            ];
        }

        return [
            'kkm' => $kkm,
            'students' => $rows,
            'meta' => [
                'class_id' => $classId,
                'class_name' => $schoolClass?->name,
                'subject_id' => $subjectId,
                'semester_id' => $semesterId,
                'count' => count($rows),
            ],
        ];
    }

    public function list(
        int $institutionId,
        array $filters = [],
        ?int $scopeEmployeeId = null
    ): array {
        $query = GradeRemedial::query()
            ->with([
                'student:id,name,nis,nisn',
                'schoolClass:id,name',
                'subject:id,name,code',
                'employee:id,name',
            ])
            ->where('institution_id', $institutionId);

        if (!empty($filters['semester_id'])) {
            $query->where('semester_id', (int) $filters['semester_id']);
        }
        if (!empty($filters['class_id'])) {
            $query->where('class_id', (int) $filters['class_id']);
        }
        if (!empty($filters['subject_id'])) {
            $query->where('subject_id', (int) $filters['subject_id']);
        }
        if (!empty($filters['student_id'])) {
            $query->where('student_id', (int) $filters['student_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if ($scopeEmployeeId) {
            $query->where('employee_id', $scopeEmployeeId);
        }

        return $query->orderByDesc('id')->get()->map(fn (GradeRemedial $r) => $this->toArray($r))->all();
    }

    public function create(int $institutionId, array $data, ?int $employeeId = null): GradeRemedial
    {
        $type = $data['type'] ?? GradeRemedial::TYPE_REMEDIAL;
        if (!isset(GradeRemedial::TYPES[$type])) {
            $type = GradeRemedial::TYPE_REMEDIAL;
        }

        $sourceType = $data['source_grade_type'] ?? Grade::TYPE_NILAI_AKHIR;
        if (!Grade::isValidType((string) $sourceType)) {
            throw new \InvalidArgumentException('Jenis nilai sumber remidi tidak valid.');
        }

        $classId = (int) $data['class_id'];
        $studentId = (int) $data['student_id'];
        $subjectId = (int) $data['subject_id'];
        $semesterId = (int) $data['semester_id'];

        $classOk = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->exists();
        if (!$classOk) {
            throw new \InvalidArgumentException('Kelas tidak ditemukan di institusi ini.');
        }

        $studentOk = Student::query()
            ->where('id', $studentId)
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->exists();
        if (!$studentOk) {
            throw new \InvalidArgumentException('Siswa tidak ditemukan di kelas yang dipilih.');
        }

        $original = $data['original_value'] ?? null;
        if ($original === null) {
            $original = Grade::query()
                ->where('institution_id', $institutionId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('semester_id', $semesterId)
                ->where('student_id', $studentId)
                ->where('grade_type', $sourceType)
                ->value('value');
        }

        $kkm = $data['kkm_snapshot'] ?? null;
        if ($kkm === null) {
            $gradeLevel = (int) (SchoolClass::where('id', $classId)->value('grade') ?? 0);
            if ($gradeLevel > 0) {
                $kkm = SubjectKkm::query()
                    ->where('institution_id', $institutionId)
                    ->where('subject_id', $subjectId)
                    ->where('grade', $gradeLevel)
                    ->where('semester_id', $semesterId)
                    ->value('kkm');
            }
        }

        return GradeRemedial::create([
            'institution_id' => $institutionId,
            'semester_id' => $semesterId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'student_id' => $studentId,
            'employee_id' => $employeeId,
            'type' => $type,
            'source_grade_type' => $sourceType,
            'original_value' => $original !== null ? (float) $original : null,
            'kkm_snapshot' => $kkm !== null ? (float) $kkm : null,
            'scheduled_date' => $data['scheduled_date'] ?? null,
            'status' => GradeRemedial::STATUS_PLANNED,
            'apply_to_grade' => array_key_exists('apply_to_grade', $data)
                ? (bool) $data['apply_to_grade']
                : true,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function complete(GradeRemedial $remedial, array $data, ?int $employeeId = null): GradeRemedial
    {
        if ($remedial->status !== GradeRemedial::STATUS_PLANNED) {
            throw new \InvalidArgumentException('Hanya remidi berstatus direncanakan yang dapat diselesaikan.');
        }

        $value = isset($data['remedial_value']) ? (float) $data['remedial_value'] : null;
        if ($value === null || $value < 0 || $value > 100) {
            throw new \InvalidArgumentException('Nilai remidi wajib diisi (0–100).');
        }

        return DB::transaction(function () use ($remedial, $data, $value, $employeeId) {
            $remedial->remedial_value = $value;
            $remedial->completed_date = $data['completed_date'] ?? Carbon::today()->toDateString();
            $remedial->status = GradeRemedial::STATUS_COMPLETED;
            if (array_key_exists('notes', $data)) {
                $remedial->notes = $data['notes'];
            }
            if (array_key_exists('apply_to_grade', $data)) {
                $remedial->apply_to_grade = (bool) $data['apply_to_grade'];
            }
            if ($employeeId && !$remedial->employee_id) {
                $remedial->employee_id = $employeeId;
            }
            $remedial->save();

            if ($remedial->apply_to_grade) {
                $this->applyRemedialToGrade($remedial, $value, $employeeId);
            }

            return $remedial->fresh([
                'student:id,name,nis,nisn',
                'schoolClass:id,name',
                'subject:id,name,code',
                'employee:id,name',
            ]);
        });
    }

    public function cancel(GradeRemedial $remedial, ?string $notes = null): GradeRemedial
    {
        if ($remedial->status === GradeRemedial::STATUS_COMPLETED) {
            throw new \InvalidArgumentException('Remidi yang sudah selesai tidak dapat dibatalkan.');
        }
        if ($remedial->status === GradeRemedial::STATUS_CANCELLED) {
            throw new \InvalidArgumentException('Remidi ini sudah dibatalkan.');
        }

        $remedial->status = GradeRemedial::STATUS_CANCELLED;
        if ($notes !== null) {
            $remedial->notes = $notes;
        }
        $remedial->save();

        return $remedial->fresh([
            'student:id,name,nis,nisn',
            'schoolClass:id,name',
            'subject:id,name,code',
            'employee:id,name',
        ]);
    }

    private function applyRemedialToGrade(GradeRemedial $remedial, float $value, ?int $employeeId): void
    {
        $sourceType = $remedial->source_grade_type ?: Grade::TYPE_NILAI_AKHIR;
        $semesterId = (int) $remedial->semester_id;
        $institutionId = (int) $remedial->institution_id;
        $classId = (int) $remedial->class_id;
        $subjectId = (int) $remedial->subject_id;
        $studentId = (int) $remedial->student_id;

        // Merge existing scores so partial remedi (e.g. penilaian_2 only)
        // does not recompute nilai_akhir from an incomplete payload.
        $existing = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->get();

        $penilaian = [];
        $uts = null;
        $uas = null;
        foreach ($existing as $row) {
            $type = (string) $row->grade_type;
            if (str_starts_with($type, 'penilaian_')) {
                $idx = Grade::penilaianIndex($type);
                if ($idx) {
                    $penilaian[(string) $idx] = (float) $row->value;
                }
            } elseif ($type === Grade::TYPE_UTS) {
                $uts = (float) $row->value;
            } elseif ($type === Grade::TYPE_UAS) {
                $uas = (float) $row->value;
            }
        }

        $payload = [
            'student_id' => $studentId,
            'penilaian' => $penilaian,
            'uts' => $uts,
            'uas' => $uas,
        ];

        if ($sourceType === Grade::TYPE_UTS || $sourceType === Grade::TYPE_UAS) {
            $payload[$sourceType] = $value;
        } elseif ($sourceType === Grade::TYPE_NILAI_AKHIR) {
            $payload[Grade::TYPE_NILAI_AKHIR] = $value;
        } elseif (str_starts_with($sourceType, 'penilaian_')) {
            $idx = Grade::penilaianIndex($sourceType);
            if ($idx) {
                $payload['penilaian'][(string) $idx] = $value;
            } else {
                $payload[Grade::TYPE_NILAI_AKHIR] = $value;
            }
        } else {
            $payload[Grade::TYPE_NILAI_AKHIR] = $value;
        }

        $weights = $this->gradeService->resolveWeights(
            $institutionId,
            $classId,
            $subjectId,
            $semesterId
        );

        $this->gradeService->bulkUpsert(
            $institutionId,
            $semesterId,
            $classId,
            $subjectId,
            [$payload],
            $employeeId ?? $remedial->employee_id,
            $weights
        );
    }

    public function toArray(GradeRemedial $r): array
    {
        return [
            'id' => $r->id,
            'institution_id' => $r->institution_id,
            'semester_id' => $r->semester_id,
            'class_id' => $r->class_id,
            'class_name' => $r->schoolClass?->name,
            'subject_id' => $r->subject_id,
            'subject_name' => $r->subject?->name,
            'student_id' => $r->student_id,
            'student' => $r->student ? [
                'id' => $r->student->id,
                'name' => $r->student->name,
                'nis' => $r->student->nis,
                'nisn' => $r->student->nisn,
            ] : null,
            'employee_id' => $r->employee_id,
            'employee_name' => $r->employee?->name,
            'type' => $r->type,
            'type_label' => GradeRemedial::TYPES[$r->type] ?? $r->type,
            'source_grade_type' => $r->source_grade_type,
            'original_value' => $r->original_value !== null ? (float) $r->original_value : null,
            'kkm_snapshot' => $r->kkm_snapshot !== null ? (float) $r->kkm_snapshot : null,
            'scheduled_date' => optional($r->scheduled_date)?->format('Y-m-d'),
            'completed_date' => optional($r->completed_date)?->format('Y-m-d'),
            'remedial_value' => $r->remedial_value !== null ? (float) $r->remedial_value : null,
            'status' => $r->status,
            'status_label' => GradeRemedial::STATUSES[$r->status] ?? $r->status,
            'apply_to_grade' => (bool) $r->apply_to_grade,
            'notes' => $r->notes,
            'created_at' => optional($r->created_at)?->toIso8601String(),
            'updated_at' => optional($r->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * Completeness + deadline summary for class+subject+semester.
     *
     * @return array<string, mixed>
     */
    public function completeness(
        int $institutionId,
        int $classId,
        int $subjectId,
        int $semesterId
    ): array {
        $students = Student::query()
            ->where('class_id', $classId)
            ->where('institution_id', $institutionId)
            ->get(['id', 'name', 'nis']);

        $studentCount = $students->count();
        $weights = $this->gradeService->resolveWeights($institutionId, $classId, $subjectId, $semesterId);
        $assessmentCount = max(1, (int) $weights['assessment_count']);
        $assessmentMax = max(
            $assessmentCount,
            $this->gradeService->maxPenilaianIndex($institutionId, $classId, $subjectId, $semesterId)
        );

        $schoolClass = SchoolClass::query()
            ->where('id', $classId)
            ->where('institution_id', $institutionId)
            ->first(['id', 'name', 'grade']);

        $gradeLevel = (int) ($schoolClass?->grade ?? 0);
        $kkm = null;
        if ($gradeLevel > 0) {
            $kkmRow = SubjectKkm::query()
                ->where('institution_id', $institutionId)
                ->where('subject_id', $subjectId)
                ->where('grade', $gradeLevel)
                ->where('semester_id', $semesterId)
                ->first();
            $kkm = $kkmRow?->kkm !== null ? (float) $kkmRow->kkm : null;
        }

        $grades = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->get(['student_id', 'grade_type', 'value']);

        $byStudent = $grades->groupBy('student_id');
        $filledNilaiAkhir = 0;
        $tuntas = 0;
        $belumTuntas = 0;
        $filledUts = 0;
        $filledUas = 0;
        $penilaianSlotsFilled = 0;
        $penilaianSlotsExpected = $studentCount * $assessmentMax;

        $missingStudents = [];

        foreach ($students as $student) {
            $sid = (int) $student->id;
            $sg = $byStudent->get($sid, collect());
            $nilaiAkhir = $sg->firstWhere('grade_type', Grade::TYPE_NILAI_AKHIR)?->value;
            $uts = $sg->firstWhere('grade_type', Grade::TYPE_UTS)?->value;
            $uas = $sg->firstWhere('grade_type', Grade::TYPE_UAS)?->value;

            $penilaianFilled = 0;
            for ($i = 1; $i <= $assessmentMax; $i++) {
                $type = Grade::penilaianType($i);
                $v = $sg->firstWhere('grade_type', $type)?->value;
                if ($v !== null && $v !== '') {
                    $penilaianFilled++;
                    $penilaianSlotsFilled++;
                }
            }

            if ($uts !== null && $uts !== '') {
                $filledUts++;
            }
            if ($uas !== null && $uas !== '') {
                $filledUas++;
            }

            $hasNilaiAkhir = $nilaiAkhir !== null && $nilaiAkhir !== '';
            if ($hasNilaiAkhir) {
                $filledNilaiAkhir++;
                $isTuntas = SubjectKkm::isTuntas((float) $nilaiAkhir, $kkm);
                if ($isTuntas === true) {
                    $tuntas++;
                } elseif ($isTuntas === false) {
                    $belumTuntas++;
                }
            }

            $expectedSlots = $assessmentMax + 2; // penilaian* + uts + uas
            $filledSlots = $penilaianFilled
                + (($uts !== null && $uts !== '') ? 1 : 0)
                + (($uas !== null && $uas !== '') ? 1 : 0);

            if (!$hasNilaiAkhir || $filledSlots < $expectedSlots) {
                $missingStudents[] = [
                    'student_id' => $sid,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'penilaian_filled' => $penilaianFilled,
                    'penilaian_expected' => $assessmentMax,
                    'has_uts' => $uts !== null && $uts !== '',
                    'has_uas' => $uas !== null && $uas !== '',
                    'has_nilai_akhir' => $hasNilaiAkhir,
                    'nilai_akhir' => $hasNilaiAkhir ? (float) $nilaiAkhir : null,
                    'is_tuntas' => $hasNilaiAkhir ? SubjectKkm::isTuntas((float) $nilaiAkhir, $kkm) : null,
                ];
            }
        }

        $pct = static function (int $num, int $den): float {
            return $den > 0 ? round(($num / $den) * 100, 1) : 0.0;
        };

        $today = Carbon::today()->toDateString();
        $deadlines = [
            'penilaian' => $weights['deadline_penilaian'] ?? null,
            'uts' => $weights['deadline_uts'] ?? null,
            'uas' => $weights['deadline_uas'] ?? null,
            'nilai_akhir' => $weights['deadline_nilai_akhir'] ?? null,
        ];

        $deadlineStatus = [];
        foreach ($deadlines as $key => $due) {
            if (!$due) {
                $deadlineStatus[$key] = ['due_date' => null, 'status' => 'unset'];
                continue;
            }
            $dueStr = is_string($due) ? $due : Carbon::parse($due)->toDateString();
            if ($dueStr < $today) {
                $status = 'overdue';
            } elseif ($dueStr === $today) {
                $status = 'due_today';
            } else {
                $status = 'upcoming';
            }
            $deadlineStatus[$key] = [
                'due_date' => $dueStr,
                'status' => $status,
                'days_left' => (int) Carbon::today()->diffInDays(Carbon::parse($dueStr), false),
            ];
        }

        return [
            'class_id' => $classId,
            'class_name' => $schoolClass?->name,
            'subject_id' => $subjectId,
            'semester_id' => $semesterId,
            'student_count' => $studentCount,
            'kkm' => $kkm,
            'assessment_count' => $assessmentMax,
            'filled' => [
                'nilai_akhir' => $filledNilaiAkhir,
                'uts' => $filledUts,
                'uas' => $filledUas,
                'penilaian_slots' => $penilaianSlotsFilled,
                'penilaian_slots_expected' => $penilaianSlotsExpected,
            ],
            'percent' => [
                'nilai_akhir' => $pct($filledNilaiAkhir, $studentCount),
                'uts' => $pct($filledUts, $studentCount),
                'uas' => $pct($filledUas, $studentCount),
                'penilaian' => $pct($penilaianSlotsFilled, $penilaianSlotsExpected),
                'tuntas' => $pct($tuntas, $studentCount),
            ],
            'tuntas_count' => $tuntas,
            'belum_tuntas_count' => $belumTuntas,
            'belum_isi_nilai_akhir' => max(0, $studentCount - $filledNilaiAkhir),
            'deadlines' => $deadlineStatus,
            'weights_set' => (bool) ($weights['set'] ?? false),
            'missing_students' => $missingStudents,
        ];
    }
}
