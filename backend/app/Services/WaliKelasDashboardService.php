<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\StudentMutation;
use App\Models\User;
use App\Models\Violation;
use App\Support\WaliKelasAccess;
use Illuminate\Support\Facades\DB;

class WaliKelasDashboardService
{
    public function __construct(
        protected StudentPointService $studentPointService
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user, SchoolClass $class): array
    {
        $institutionId = (int) $class->institution_id;
        $institution = Institution::find($institutionId);
        $academicYearId = $institution?->active_academic_year_id;
        $semesterId = $institution?->active_semester_id;

        $studentsQuery = Student::query()
            ->where('class_id', $class->id)
            ->where('status', 'Aktif');

        $total = (clone $studentsQuery)->count();
        $genderRows = (clone $studentsQuery)
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $male = 0;
        $female = 0;
        foreach ($genderRows as $gender => $count) {
            $g = strtolower((string) $gender);
            if (in_array($g, ['l', 'male', 'laki-laki'], true)) {
                $male += (int) $count;
            } elseif (in_array($g, ['p', 'female', 'perempuan'], true)) {
                $female += (int) $count;
            }
        }

        $studentIds = (clone $studentsQuery)->pluck('id');

        return [
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'grade' => $class->grade,
            ],
            'students' => [
                'total' => $total,
                'male' => $male,
                'female' => $female,
            ],
            'attendance_today' => $this->attendanceToday($institutionId, $studentIds),
            'bk_high_scores' => $this->bkHighScores($institutionId, $studentIds, $academicYearId, $semesterId),
            'grades_incomplete' => $this->gradesIncompleteCount($institutionId, $class->id, $studentIds, $semesterId),
            'pending' => [
                'violations' => Violation::query()
                    ->where('institution_id', $institutionId)
                    ->where('reported_by', $user->id)
                    ->where('status', Violation::STATUS_PENDING)
                    ->whereIn('student_id', $studentIds)
                    ->count(),
                'achievements' => Achievement::query()
                    ->where('institution_id', $institutionId)
                    ->where('given_by', $user->id)
                    ->where('status', Achievement::STATUS_PENDING)
                    ->whereIn('student_id', $studentIds)
                    ->count(),
                'mutations' => StudentMutation::query()
                    ->where('origin_institution_id', $institutionId)
                    ->where('requested_by', $user->id)
                    ->where('source', 'wali')
                    ->where('status', 'pending')
                    ->whereIn('student_id', $studentIds)
                    ->count(),
            ],
            'is_homeroom' => WaliKelasAccess::resolveHomeroomClass($user, (int) $class->id) !== null,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $studentIds
     * @return array{hadir:int,alpha:int,izin:int,sakit:int,dinas_luar:int,students_recorded:int}
     */
    protected function attendanceToday(int $institutionId, $studentIds): array
    {
        $empty = [
            'hadir' => 0,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'dinas_luar' => 0,
            'students_recorded' => 0,
        ];

        if ($studentIds->isEmpty()) {
            return $empty;
        }

        $today = now()->toDateString();

        // Ambil status terakhir per siswa hari ini (jika multi sesi).
        $rows = StudentAttendance::query()
            ->select('student_attendances.student_id', 'student_attendances.status')
            ->join('teaching_journals', 'teaching_journals.id', '=', 'student_attendances.teaching_journal_id')
            ->where('student_attendances.institution_id', $institutionId)
            ->whereIn('student_attendances.student_id', $studentIds->all())
            ->whereDate('teaching_journals.journal_date', $today)
            ->whereNull('student_attendances.deleted_at')
            ->orderByDesc('student_attendances.id')
            ->get();

        $latestByStudent = [];
        foreach ($rows as $row) {
            $sid = (int) $row->student_id;
            if (!isset($latestByStudent[$sid])) {
                $latestByStudent[$sid] = (string) $row->status;
            }
        }

        $counts = $empty;
        $counts['students_recorded'] = count($latestByStudent);
        foreach ($latestByStudent as $status) {
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $studentIds
     * @return array{count:int,threshold:int,top:array<int, array{student_id:int,name:string,score:int}>}
     */
    protected function bkHighScores(int $institutionId, $studentIds, ?int $academicYearId, ?int $semesterId): array
    {
        $threshold = 20;
        $top = [];

        if ($studentIds->isEmpty()) {
            return ['count' => 0, 'threshold' => $threshold, 'top' => []];
        }

        $students = Student::query()
            ->whereIn('id', $studentIds->all())
            ->get(['id', 'name']);

        $high = [];
        foreach ($students as $student) {
            $score = $this->studentPointService->getTotalPoint(
                (int) $student->id,
                $institutionId,
                $academicYearId,
                $semesterId
            );
            if ($score >= $threshold) {
                $high[] = [
                    'student_id' => (int) $student->id,
                    'name' => $student->name,
                    'score' => $score,
                ];
            }
        }

        usort($high, fn ($a, $b) => $b['score'] <=> $a['score']);

        return [
            'count' => count($high),
            'threshold' => $threshold,
            'top' => array_slice($high, 0, 5),
        ];
    }

    /**
     * Siswa aktif tanpa nilai di semester aktif (estimasi ringan).
     *
     * @param  \Illuminate\Support\Collection<int, int>  $studentIds
     */
    protected function gradesIncompleteCount(int $institutionId, int $classId, $studentIds, ?int $semesterId): int
    {
        if ($studentIds->isEmpty() || !$semesterId) {
            return 0;
        }

        $withGrades = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->whereIn('student_id', $studentIds->all())
            ->distinct()
            ->pluck('student_id');

        return max(0, $studentIds->count() - $withGrades->count());
    }
}
