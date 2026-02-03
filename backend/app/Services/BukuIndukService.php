<?php

namespace App\Services;

use App\Models\Grade;
use App\Models\StudentAttendance;
use Illuminate\Support\Facades\Log;

class BukuIndukService
{
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
        ];

        $student = $this->studentService->find($studentId, $with);

        $attendanceSummary = $this->buildAttendanceSummary($student->id);
        $gradesSummary = $this->buildGradesSummary($student->id);
        $librarySummary = $this->buildLibrarySummary($student);

        $extracurriculars = $student->extracurricularEnrollments
            ->sortByDesc('joined_at')
            ->values();

        return [
            'student' => $student,
            'institution' => $student->institution,
            'class_history' => $student->classHistory->sortBy('start_date')->values(),
            'mutations' => $student->studentMutations->sortByDesc('created_at')->values(),
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
     * Build grades summary (nilai akhir per mapel per semester).
     */
    protected function buildGradesSummary(int $studentId): array
    {
        $grades = Grade::where('student_id', $studentId)
            ->where('grade_type', Grade::TYPE_NILAI_AKHIR)
            ->with(['subject', 'academicYear', 'semester'])
            ->orderBy('academic_year_id')
            ->orderBy('semester_id')
            ->get();

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
            $byPeriod[$key]['subjects'][] = [
                'subject_name' => $g->subject?->name ?? '-',
                'subject_code' => $g->subject?->code ?? null,
                'value' => $g->value !== null ? (float) $g->value : null,
            ];
        }

        return array_values($byPeriod);
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
