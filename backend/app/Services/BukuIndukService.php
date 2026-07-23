<?php

namespace App\Services;

use App\Models\ExtracurricularGrade;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\StudentAttendance;
use App\Models\SubjectKkm;
use Illuminate\Support\Collection;

class BukuIndukService
{
    private const MUTATION_STATUS_LABELS = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
        'cancel_pending' => 'Menunggu batal',
    ];

    public function __construct(
        private StudentService $studentService
    ) {}

    /**
     * Get buku induk data for one student (for JSON response and PDF).
     */
    public function getDataForStudent(int $studentId): array
    {
        $with = [
            'institution',
            'class',
            'academicYear',
            'semester',
            'classHistory.class',
            'classHistory.academicYear',
            'classHistory.semester',
            'studentMutations.originInstitution:id,name,npsn',
            'studentMutations.targetInstitution:id,name,npsn',
            'achievements.achievementType',
            'achievements.academicYear',
            'violations.violationType',
            'violations.academicYear',
            'counselingSessions.counselingType',
            'documentPickups',
            'extracurricularEnrollments.extracurricular',
            'extracurricularEnrollments.academicYear',
            'extracurricularEnrollments.semester',
            'alumniDestinations',
            'libraryLoans',
        ];

        $student = $this->studentService->find($studentId, $with);

        $attendanceSummary = $this->buildAttendanceSummary($student->id);
        $gradesSummary = $this->buildGradesSummary($student);
        $librarySummary = $this->buildLibrarySummary($student);
        $extracurriculars = $this->buildExtracurricularSummary($student);
        $mutations = $this->buildMutationsSummary($student->studentMutations);

        return [
            'student' => $student,
            'institution' => $student->institution,
            'class_history' => $student->classHistory->sortBy('start_date')->values(),
            'mutations' => $mutations,
            'achievements' => $student->achievements->sortByDesc('achievement_date')->values(),
            'violations' => $student->violations->sortByDesc('violation_date')->values(),
            'counseling_sessions' => $student->counselingSessions->sortByDesc('session_date')->values(),
            'document_pickups' => $student->documentPickups->sortByDesc('pickup_date')->values(),
            'attendance_summary' => $attendanceSummary,
            'grades_summary' => $gradesSummary,
            'extracurriculars' => $extracurriculars,
            'alumni_destinations' => $student->alumniDestinations->sortByDesc('year_entered')->values(),
            'library_loans_summary' => $librarySummary,
            'health_records' => [], // Placeholder: data dari modul UKS ketika tersedia
            'printed_at' => now()->locale('id')->isoFormat('D MMMM YYYY HH:mm'),
        ];
    }

    /**
     * Build attendance summary grouped by semester.
     */
    protected function buildAttendanceSummary(int $studentId): array
    {
        $attendances = StudentAttendance::where('student_id', $studentId)
            ->with('teachingJournal.semester.academicYear')
            ->get();

        $byPeriod = [];
        foreach ($attendances as $att) {
            $tj = $att->teachingJournal;
            if (!$tj || !$tj->relationLoaded('semester')) {
                continue;
            }
            $sem = $tj->semester;
            $ay = $sem && $sem->relationLoaded('academicYear') ? $sem->academicYear : null;
            $key = ($ay ? $ay->id : 0) . '_' . ($sem ? $sem->id : 0);
            if (!isset($byPeriod[$key])) {
                $byPeriod[$key] = [
                    'academic_year_id' => $ay?->id,
                    'academic_year_name' => $ay?->name ?? '-',
                    'semester_id' => $sem?->id,
                    'semester_name' => $sem?->name ?? '-',
                    'hadir' => 0,
                    'sakit' => 0,
                    'izin' => 0,
                    'alpha' => 0,
                    'dinas_luar' => 0,
                ];
            }
            $stat = $att->status ?? 'hadir';
            if (isset($byPeriod[$key][$stat])) {
                $byPeriod[$key][$stat]++;
            }
        }

        return array_values($byPeriod);
    }

    /**
     * Build grades summary (nilai akhir + KKM/predikat per mapel per semester).
     */
    protected function buildGradesSummary($student): array
    {
        $grades = Grade::where('student_id', $student->id)
            ->where('grade_type', Grade::TYPE_NILAI_AKHIR)
            ->with(['subject', 'academicYear', 'semester', 'schoolClass:id,grade'])
            ->orderBy('academic_year_id')
            ->orderBy('semester_id')
            ->get();

        if ($grades->isEmpty()) {
            return [];
        }

        $classIds = $grades->pluck('class_id')->filter()->unique()->values();
        $classGradeById = SchoolClass::query()
            ->whereIn('id', $classIds)
            ->pluck('grade', 'id');

        $kkmCache = [];
        $byPeriod = [];

        foreach ($grades as $g) {
            $key = ($g->academic_year_id ?? 0) . '_' . ($g->semester_id ?? 0);
            if (!isset($byPeriod[$key])) {
                $byPeriod[$key] = [
                    'academic_year_id' => $g->academic_year_id,
                    'academic_year_name' => $g->academicYear?->name ?? '-',
                    'semester_id' => $g->semester_id,
                    'semester_name' => $g->semester?->name ?? '-',
                    'subjects' => [],
                ];
            }

            $value = $g->value !== null ? (float) $g->value : null;
            $gradeLevel = (int) (
                $classGradeById[$g->class_id] ??
                $g->schoolClass?->grade ??
                $student->tingkat ??
                0
            );
            $kkm = $this->resolveKkm(
                $kkmCache,
                (int) ($g->institution_id ?? $student->institution_id),
                (int) ($g->semester_id ?? 0),
                $gradeLevel,
                (int) ($g->subject_id ?? 0)
            );
            $tuntas = SubjectKkm::isTuntas($value, $kkm);

            $byPeriod[$key]['subjects'][] = [
                'subject_name' => $g->subject?->name ?? '-',
                'subject_code' => $g->subject?->code ?? null,
                'value' => $value,
                'kkm' => $kkm,
                'predicate' => SubjectKkm::predicateFromScore($value, $kkm),
                'is_tuntas' => $tuntas,
                'tuntas_label' => $tuntas === null ? null : ($tuntas ? 'Tuntas' : 'Belum tuntas'),
            ];
        }

        return array_values($byPeriod);
    }

    /**
     * @param  array<string, float|null>  $kkmCache
     */
    protected function resolveKkm(array &$kkmCache, int $institutionId, int $semesterId, int $gradeLevel, int $subjectId): ?float
    {
        if ($institutionId <= 0 || $semesterId <= 0 || $gradeLevel <= 0 || $subjectId <= 0) {
            return null;
        }

        $cacheKey = "{$institutionId}_{$semesterId}_{$gradeLevel}_{$subjectId}";
        if (array_key_exists($cacheKey, $kkmCache)) {
            return $kkmCache[$cacheKey];
        }

        $kkm = SubjectKkm::query()
            ->where('institution_id', $institutionId)
            ->where('semester_id', $semesterId)
            ->where('grade', $gradeLevel)
            ->where('subject_id', $subjectId)
            ->value('kkm');

        $kkmCache[$cacheKey] = $kkm !== null ? (float) $kkm : null;

        return $kkmCache[$cacheKey];
    }

    /**
     * Normalize mutation rows for UI/PDF (status label, cancel flow, NPSN).
     */
    protected function buildMutationsSummary(Collection $mutations): Collection
    {
        return $mutations
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($m) {
                $originName = $m->originInstitution?->name ?? $m->origin_school_name;
                $targetName = $m->targetInstitution?->name ?? $m->target_school_name;
                $originNpsn = $m->originInstitution?->npsn ?? $m->origin_npsn;
                $targetNpsn = $m->targetInstitution?->npsn ?? $m->target_npsn;
                $status = $m->status ?? '-';

                return [
                    'id' => $m->id,
                    'origin_school_name' => $originName,
                    'origin_npsn' => $originNpsn,
                    'target_school_name' => $targetName,
                    'target_npsn' => $targetNpsn,
                    'origin_institution' => $m->originInstitution ? [
                        'id' => $m->originInstitution->id,
                        'name' => $m->originInstitution->name,
                        'npsn' => $m->originInstitution->npsn,
                    ] : null,
                    'target_institution' => $m->targetInstitution ? [
                        'id' => $m->targetInstitution->id,
                        'name' => $m->targetInstitution->name,
                        'npsn' => $m->targetInstitution->npsn,
                    ] : null,
                    'status' => $status,
                    'status_label' => self::MUTATION_STATUS_LABELS[$status] ?? $status,
                    'student_grade' => $m->student_grade,
                    'notes' => $m->notes,
                    'rejection_reason' => $m->rejection_reason,
                    'cancel_reason' => $m->cancel_reason,
                    'cancel_rejection_reason' => $m->cancel_rejection_reason,
                    'approved_at' => $m->approved_at?->toIso8601String(),
                    'cancel_requested_at' => $m->cancel_requested_at?->toIso8601String(),
                    'created_at' => $m->created_at?->toIso8601String(),
                ];
            });
    }

    /**
     * Enrollment + nilai akhir ekskul (KKM/predikat).
     */
    protected function buildExtracurricularSummary($student): Collection
    {
        $enrollments = $student->extracurricularEnrollments
            ->sortByDesc('joined_at')
            ->values();

        if ($enrollments->isEmpty()) {
            return collect();
        }

        $finalGrades = ExtracurricularGrade::query()
            ->where('student_id', $student->id)
            ->whereIn('extracurricular_id', $enrollments->pluck('extracurricular_id')->filter()->unique())
            ->get()
            ->keyBy(fn ($g) => $g->extracurricular_id . '_' . ($g->semester_id ?? 0));

        return $enrollments->map(function ($e) use ($finalGrades) {
            $ekskul = $e->extracurricular;
            $kkm = $ekskul?->kkm_value ?? 75.0;
            $key = $e->extracurricular_id . '_' . ($e->semester_id ?? 0);
            $final = $finalGrades->get($key)
                ?? $finalGrades->first(fn ($g) => (int) $g->extracurricular_id === (int) $e->extracurricular_id);

            $score = $final?->score !== null ? (float) $final->score : null;
            $predicate = $final?->predicate
                ?? ExtracurricularGrade::predicateFromScore($score, $kkm);

            return [
                'id' => $e->id,
                'extracurricular' => $ekskul ? [
                    'id' => $ekskul->id,
                    'name' => $ekskul->name,
                    'kkm' => $kkm,
                ] : null,
                'academic_year' => $e->academicYear ? [
                    'id' => $e->academicYear->id,
                    'name' => $e->academicYear->name,
                ] : null,
                'semester' => $e->semester ? [
                    'id' => $e->semester->id,
                    'name' => $e->semester->name,
                ] : null,
                'joined_at' => $e->joined_at?->toIso8601String(),
                'left_at' => $e->left_at?->toIso8601String(),
                'status' => $e->status,
                'kkm' => $kkm,
                'score' => $score,
                'predicate' => $predicate,
                'grade_notes' => $final?->notes,
            ];
        });
    }

    /**
     * Build library loans summary for the student.
     */
    protected function buildLibrarySummary($student): array
    {
        if (!method_exists($student, 'libraryLoans')) {
            return ['total_loans' => 0, 'late_count' => 0, 'overdue_count' => 0];
        }
        $loans = $student->libraryLoans;
        $total = $loans->count();
        $lateCount = $loans->filter(function ($loan) {
            return $loan->status === 'Terlambat' || ($loan->returned_at && $loan->due_date && $loan->returned_at->gt($loan->due_date));
        })->count();
        $overdueCount = $loans->whereIn('status', ['Dipinjam', 'Terlambat'])->filter(function ($loan) {
            return $loan->due_date && $loan->due_date->isPast() && !$loan->returned_at;
        })->count();

        return [
            'total_loans' => $total,
            'late_count' => $lateCount,
            'overdue_count' => $overdueCount,
        ];
    }
}
