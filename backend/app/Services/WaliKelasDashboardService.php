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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WaliKelasDashboardService
{
    public function __construct(
        protected StudentPointService $studentPointService,
        protected GradeService $gradeService,
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
        $incomplete = $this->gradesIncomplete($institutionId, $class->id, $studentIds, $semesterId);
        $accounts = $this->accountStatusForClass($class->id);

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
            'accounts' => $accounts,
            'attendance_today' => $this->attendanceToday($institutionId, $studentIds),
            'bk_high_scores' => $this->bkHighScores($institutionId, $studentIds, $academicYearId, $semesterId),
            'grades_incomplete' => $incomplete['count'],
            'grades_incomplete_students' => $incomplete['students'],
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
     * @return array{
     *     with_account: int,
     *     missing_account: int,
     *     incomplete_data: int,
     *     missing_students: list<array{student_id: int, name: string, nik: ?string}>,
     *     incomplete_students: list<array{student_id: int, name: string, nik: ?string, reason: string}>
     * }
     */
    protected function accountStatusForClass(int $classId): array
    {
        $base = Student::query()
            ->where('class_id', $classId)
            ->where('status', 'Aktif');

        $hasAccount = function ($q) {
            $q->select(DB::raw(1))
                ->from('user')
                ->whereColumn('user.login_nik', 'student.nik')
                ->where('user.role', 'student');
        };

        $withAccount = (clone $base)->whereExists($hasAccount)->count();

        $missingQuery = (clone $base)
            ->whereNotNull('nik')
            ->where('nik', '!=', '')
            ->whereRaw("TRIM(nik) REGEXP '^[0-9]{16}$'")
            ->whereNotNull('birth_date')
            ->whereNotExists($hasAccount);

        $missingAccount = (clone $missingQuery)->count();
        $missingStudents = (clone $missingQuery)
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'nik'])
            ->map(fn (Student $s) => [
                'student_id' => $s->id,
                'name' => $s->name,
                'nik' => $s->nik,
            ])
            ->values()
            ->all();

        $incompleteQuery = (clone $base)->where(function ($q) {
            $q->whereNull('nik')
                ->orWhere('nik', '')
                ->orWhereRaw("TRIM(nik) NOT REGEXP '^[0-9]{16}$'")
                ->orWhereNull('birth_date');
        });

        $incompleteData = (clone $incompleteQuery)->count();
        $incompleteStudents = (clone $incompleteQuery)
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'nik', 'birth_date'])
            ->map(function (Student $s) {
                $reasons = [];
                $nik = trim((string) ($s->nik ?? ''));
                if ($nik === '' || preg_match('/^\d{16}$/', $nik) !== 1) {
                    $reasons[] = 'NIK';
                }
                if (empty($s->birth_date)) {
                    $reasons[] = 'tanggal lahir';
                }

                return [
                    'student_id' => $s->id,
                    'name' => $s->name,
                    'nik' => $s->nik,
                    'reason' => 'Belum lengkap: ' . implode(', ', $reasons),
                ];
            })
            ->values()
            ->all();

        return [
            'with_account' => $withAccount,
            'missing_account' => $missingAccount,
            'incomplete_data' => $incompleteData,
            'missing_students' => $missingStudents,
            'incomplete_students' => $incompleteStudents,
        ];
    }

    /**
     * Rekap absensi periode (minggu/bulan) + daftar alpa berulang.
     *
     * @return array<string, mixed>
     */
    public function attendanceSummary(SchoolClass $class, string $period = 'week'): array
    {
        $period = in_array($period, ['week', 'month'], true) ? $period : 'week';
        $days = $period === 'month' ? 30 : 7;
        $dateFrom = now()->subDays($days - 1)->toDateString();
        $dateTo = now()->toDateString();
        $institutionId = (int) $class->institution_id;

        $students = Student::query()
            ->where('class_id', $class->id)
            ->where('status', 'Aktif')
            ->orderBy('name')
            ->get(['id', 'name', 'nis']);

        $studentIds = $students->pluck('id');
        $emptyCounts = [
            'hadir' => 0,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'dinas_luar' => 0,
        ];

        if ($studentIds->isEmpty()) {
            return [
                'period' => $period,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'totals' => $emptyCounts + ['sessions' => 0, 'students' => 0],
                'repeat_alpha' => [],
                'rows' => [],
            ];
        }

        $rows = StudentAttendance::query()
            ->select(
                'student_attendances.student_id',
                'student_attendances.status',
                DB::raw('COUNT(*) as total')
            )
            ->join('teaching_journals', 'teaching_journals.id', '=', 'student_attendances.teaching_journal_id')
            ->where('student_attendances.institution_id', $institutionId)
            ->whereIn('student_attendances.student_id', $studentIds->all())
            ->whereDate('teaching_journals.journal_date', '>=', $dateFrom)
            ->whereDate('teaching_journals.journal_date', '<=', $dateTo)
            ->whereNull('student_attendances.deleted_at')
            ->groupBy('student_attendances.student_id', 'student_attendances.status')
            ->get();

        $byStudent = [];
        $totals = $emptyCounts;
        $sessions = 0;
        foreach ($rows as $row) {
            $sid = (int) $row->student_id;
            $status = (string) $row->status;
            $count = (int) $row->total;
            if (!isset($byStudent[$sid])) {
                $byStudent[$sid] = $emptyCounts;
            }
            if (isset($byStudent[$sid][$status])) {
                $byStudent[$sid][$status] += $count;
            }
            if (isset($totals[$status])) {
                $totals[$status] += $count;
            }
            $sessions += $count;
        }

        $alphaThreshold = $period === 'month' ? 3 : 2;
        $tableRows = [];
        $repeatAlpha = [];
        foreach ($students as $student) {
            $counts = $byStudent[(int) $student->id] ?? $emptyCounts;
            $recorded = array_sum($counts);
            $tableRows[] = [
                'student_id' => (int) $student->id,
                'name' => $student->name,
                'nis' => $student->nis,
                'counts' => $counts,
                'recorded' => $recorded,
            ];
            if ($counts['alpha'] >= $alphaThreshold) {
                $repeatAlpha[] = [
                    'student_id' => (int) $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'alpha' => $counts['alpha'],
                ];
            }
        }

        usort($repeatAlpha, fn ($a, $b) => $b['alpha'] <=> $a['alpha']);

        return [
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'alpha_threshold' => $alphaThreshold,
            'totals' => $totals + [
                'sessions' => $sessions,
                'students' => $students->count(),
            ],
            'repeat_alpha' => $repeatAlpha,
            'rows' => $tableRows,
        ];
    }

    /**
     * Ringkasan nilai semester aktif: mapel kosong & di bawah KKM.
     *
     * @return array<string, mixed>
     */
    public function gradesOverview(SchoolClass $class): array
    {
        $institution = Institution::find($class->institution_id);
        $semesterId = $institution?->active_semester_id;
        if (!$semesterId) {
            return [
                'semester_id' => null,
                'subjects' => [],
                'summary' => [
                    'students' => 0,
                    'missing_any' => 0,
                    'below_kkm_any' => 0,
                ],
                'rows' => [],
            ];
        }

        $full = $this->gradeService->getByClassSemester(
            (int) $class->institution_id,
            (int) $class->id,
            (int) $semesterId
        );

        $rows = [];
        $missingAny = 0;
        $belowKkmAny = 0;
        foreach ($full['rows'] as $row) {
            $missing = [];
            $belowKkm = [];
            foreach ($row['subjects'] as $subj) {
                if ($subj['nilai_akhir'] === null) {
                    $missing[] = [
                        'subject_id' => $subj['subject_id'],
                        'subject_name' => $subj['subject_name'],
                    ];
                } elseif ($subj['is_tuntas'] === false) {
                    $belowKkm[] = [
                        'subject_id' => $subj['subject_id'],
                        'subject_name' => $subj['subject_name'],
                        'nilai_akhir' => $subj['nilai_akhir'],
                        'kkm' => $subj['kkm'],
                    ];
                }
            }
            if ($missing) {
                $missingAny++;
            }
            if ($belowKkm) {
                $belowKkmAny++;
            }
            $rows[] = [
                'student_id' => $row['student_id'],
                'name' => $row['student']['name'] ?? '',
                'nis' => $row['student']['nis'] ?? null,
                'average' => $row['average'],
                'rank' => $row['rank'],
                'missing_count' => count($missing),
                'below_kkm_count' => count($belowKkm),
                'missing' => $missing,
                'below_kkm' => $belowKkm,
            ];
        }

        // Prioritaskan yang bermasalah di atas.
        usort($rows, function (array $a, array $b) {
            $scoreA = ($a['missing_count'] * 10) + $a['below_kkm_count'];
            $scoreB = ($b['missing_count'] * 10) + $b['below_kkm_count'];
            if ($scoreA === $scoreB) {
                return strcmp((string) $a['name'], (string) $b['name']);
            }

            return $scoreB <=> $scoreA;
        });

        return [
            'semester_id' => (int) $semesterId,
            'subjects' => $full['subjects'],
            'summary' => [
                'students' => count($rows),
                'subject_count' => count($full['subjects']),
                'missing_any' => $missingAny,
                'below_kkm_any' => $belowKkmAny,
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Snapshot 360° satu siswa untuk modal profil wali.
     *
     * @return array<string, mixed>
     */
    public function studentSnapshot(User $user, SchoolClass $class, Student $student): array
    {
        $institutionId = (int) $class->institution_id;
        $institution = Institution::find($institutionId);
        $academicYearId = $institution?->active_academic_year_id;
        $semesterId = $institution?->active_semester_id;

        $attendanceWeek = $this->attendanceCountsForStudent(
            $institutionId,
            (int) $student->id,
            now()->subDays(6)->toDateString(),
            now()->toDateString()
        );
        $attendanceMonth = $this->attendanceCountsForStudent(
            $institutionId,
            (int) $student->id,
            now()->subDays(29)->toDateString(),
            now()->toDateString()
        );

        $bk = $this->studentPointService->getPointSummary(
            (int) $student->id,
            $institutionId,
            $academicYearId,
            $semesterId
        );

        $grades = $this->gradesSnapshotForStudent($class, $student, $semesterId ? (int) $semesterId : null);

        $recentViolations = Violation::query()
            ->with('violationType:id,name,point_weight')
            ->where('institution_id', $institutionId)
            ->where('student_id', $student->id)
            ->orderByDesc('violation_date')
            ->limit(5)
            ->get(['id', 'violation_type_id', 'violation_date', 'status', 'description']);

        $recentAchievements = Achievement::query()
            ->with('achievementType:id,name')
            ->where('institution_id', $institutionId)
            ->where('student_id', $student->id)
            ->orderByDesc('achievement_date')
            ->limit(5)
            ->get(['id', 'achievement_type_id', 'achievement_date', 'status', 'point_value', 'notes']);

        $mutations = StudentMutation::query()
            ->where('student_id', $student->id)
            ->where(function ($q) use ($institutionId) {
                $q->where('origin_institution_id', $institutionId)
                    ->orWhere('target_institution_id', $institutionId);
            })
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'status', 'source', 'target_npsn', 'target_school_name', 'created_at']);

        return [
            'attendance' => [
                'week' => $attendanceWeek,
                'month' => $attendanceMonth,
            ],
            'bk' => $bk,
            'grades' => $grades,
            'recent_violations' => $recentViolations->map(fn (Violation $v) => [
                'id' => $v->id,
                'type' => $v->violationType?->name,
                'date' => optional($v->violation_date)->toDateString() ?? (string) $v->violation_date,
                'status' => $v->status,
                'points' => $v->violationType?->point_weight,
            ])->values()->all(),
            'recent_achievements' => $recentAchievements->map(fn (Achievement $a) => [
                'id' => $a->id,
                'type' => $a->achievementType?->name,
                'date' => optional($a->achievement_date)->toDateString() ?? (string) $a->achievement_date,
                'status' => $a->status,
                'points' => $a->point_value,
            ])->values()->all(),
            'mutations' => $mutations->map(fn (StudentMutation $m) => [
                'id' => $m->id,
                'status' => $m->status,
                'source' => $m->source,
                'target' => $m->target_school_name ?: $m->target_npsn,
                'created_at' => optional($m->created_at)?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, int>  $studentIds
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
     * @return array{hadir:int,alpha:int,izin:int,sakit:int,dinas_luar:int,recorded:int}
     */
    protected function attendanceCountsForStudent(int $institutionId, int $studentId, string $dateFrom, string $dateTo): array
    {
        $counts = [
            'hadir' => 0,
            'alpha' => 0,
            'izin' => 0,
            'sakit' => 0,
            'dinas_luar' => 0,
            'recorded' => 0,
        ];

        $rows = StudentAttendance::query()
            ->select('student_attendances.status', DB::raw('COUNT(*) as total'))
            ->join('teaching_journals', 'teaching_journals.id', '=', 'student_attendances.teaching_journal_id')
            ->where('student_attendances.institution_id', $institutionId)
            ->where('student_attendances.student_id', $studentId)
            ->whereDate('teaching_journals.journal_date', '>=', $dateFrom)
            ->whereDate('teaching_journals.journal_date', '<=', $dateTo)
            ->whereNull('student_attendances.deleted_at')
            ->groupBy('student_attendances.status')
            ->get();

        foreach ($rows as $row) {
            $status = (string) $row->status;
            $total = (int) $row->total;
            if (isset($counts[$status])) {
                $counts[$status] += $total;
            }
            $counts['recorded'] += $total;
        }

        return $counts;
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @return array{count:int,threshold:int,top:array<int, array{student_id:int,name:string,score:int}>}
     */
    protected function bkHighScores(int $institutionId, $studentIds, ?int $academicYearId, ?int $semesterId): array
    {
        $threshold = 20;
        $top = [];

        if ($studentIds->isEmpty()) {
            return ['count' => 0, 'threshold' => $threshold, 'top' => [], 'students' => []];
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
            'students' => $high,
        ];
    }

    /**
     * @return array{average:?float,missing_count:int,below_kkm_count:int,subjects:array<int, array<string, mixed>>}
     */
    protected function gradesSnapshotForStudent(SchoolClass $class, Student $student, ?int $semesterId): array
    {
        $empty = [
            'average' => null,
            'missing_count' => 0,
            'below_kkm_count' => 0,
            'subjects' => [],
        ];
        if (!$semesterId) {
            return $empty;
        }

        $overview = $this->gradeService->getByClassSemester(
            (int) $class->institution_id,
            (int) $class->id,
            $semesterId
        );
        $row = collect($overview['rows'])->firstWhere('student_id', (int) $student->id);
        if (!$row) {
            return $empty;
        }

        $missing = [];
        $belowKkm = [];
        foreach ($row['subjects'] as $subj) {
            if ($subj['nilai_akhir'] === null) {
                $missing[] = [
                    'subject_id' => $subj['subject_id'],
                    'subject_name' => $subj['subject_name'],
                    'status' => 'missing',
                ];
            } elseif ($subj['is_tuntas'] === false) {
                $belowKkm[] = [
                    'subject_id' => $subj['subject_id'],
                    'subject_name' => $subj['subject_name'],
                    'nilai_akhir' => $subj['nilai_akhir'],
                    'kkm' => $subj['kkm'],
                    'status' => 'below_kkm',
                ];
            }
        }

        return [
            'average' => $row['average'],
            'missing_count' => count($missing),
            'below_kkm_count' => count($belowKkm),
            'subjects' => array_merge($missing, $belowKkm),
        ];
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @return array{count:int,students:array<int, array{student_id:int,name:string}>}
     */
    protected function gradesIncomplete(int $institutionId, int $classId, $studentIds, ?int $semesterId): array
    {
        if ($studentIds->isEmpty() || !$semesterId) {
            return ['count' => 0, 'students' => []];
        }

        $withGrades = Grade::query()
            ->where('institution_id', $institutionId)
            ->where('class_id', $classId)
            ->where('semester_id', $semesterId)
            ->whereIn('student_id', $studentIds->all())
            ->distinct()
            ->pluck('student_id');

        $missingIds = $studentIds->diff($withGrades)->values();
        $students = Student::query()
            ->whereIn('id', $missingIds->all())
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Student $s) => [
                'student_id' => (int) $s->id,
                'name' => $s->name,
            ])
            ->values()
            ->all();

        return [
            'count' => count($students),
            'students' => $students,
        ];
    }
}
