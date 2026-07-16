<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Extracurricular;
use App\Models\ExtracurricularAttendance;
use App\Models\ExtracurricularGrade;
use App\Models\ExtracurricularSession;
use App\Models\ExtracurricularStudent;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Student;
use App\Support\ExtracurricularAccess;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExtracurricularActivityController extends Controller
{
    private function denyUnlessCanAccess(Request $request, Extracurricular $extracurricular): ?JsonResponse
    {
        $user = $request->user();
        if (!ExtracurricularAccess::canAccess($user, $extracurricular)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return null;
    }

    private function activeSemesterId(int $institutionId): ?int
    {
        return Institution::find($institutionId)?->active_semester_id;
    }

    private function activeAcademicYearId(int $institutionId): ?int
    {
        $institution = Institution::find($institutionId);

        return $institution?->active_academic_year_id
            ?? $institution?->activeSemester?->academic_year_id;
    }

    private function classPayload(?Student $student): ?array
    {
        if (!$student || !$student->relationLoaded('class')) {
            return null;
        }
        $related = $student->getRelation('class');
        if (!$related instanceof SchoolClass) {
            return null;
        }

        return ['id' => $related->id, 'name' => $related->name];
    }

    // ─── Sessions ─────────────────────────────────────────────

    public function listSessions(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }

        $query = $extracurricular->sessions()
            ->withCount('attendances')
            ->with(['recorder:id,name'])
            ->orderByDesc('session_date')
            ->orderByDesc('id');

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->get('semester_id'));
        } else {
            $active = $this->activeSemesterId($extracurricular->institution_id);
            if ($active) {
                $query->where('semester_id', $active);
            }
        }

        $items = $query->limit(100)->get()->map(function (ExtracurricularSession $s) {
            return [
                'id' => $s->id,
                'session_date' => $s->session_date?->format('Y-m-d'),
                'start_time' => $s->start_time?->format('H:i'),
                'end_time' => $s->end_time?->format('H:i'),
                'topic' => $s->topic,
                'notes' => $s->notes,
                'semester_id' => $s->semester_id,
                'attendances_count' => $s->attendances_count,
                'recorder' => $s->recorder ? ['id' => $s->recorder->id, 'name' => $s->recorder->name] : null,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function storeSession(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }

        $input = $request->all();
        foreach (['start_time', 'end_time'] as $key) {
            if (!empty($input[$key]) && is_string($input[$key])) {
                $input[$key] = substr($input[$key], 0, 5);
            }
        }

        $v = Validator::make($input, [
            'session_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'topic' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'with_attendance' => 'nullable|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        $employee = ExtracurricularAccess::employeeFor($request->user());
        $semesterId = $this->activeSemesterId($extracurricular->institution_id)
            ?? $extracurricular->semester_id;

        $session = null;
        DB::beginTransaction();
        try {
            $session = ExtracurricularSession::create([
                'institution_id' => $extracurricular->institution_id,
                'extracurricular_id' => $extracurricular->id,
                'semester_id' => $semesterId,
                'session_date' => $data['session_date'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'topic' => $data['topic'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $employee?->id,
            ]);

            if (!empty($data['with_attendance'])) {
                $this->seedAttendances($session, $extracurricular, $semesterId);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ekskul storeSession failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menyimpan pertemuan.'], 500);
        }

        return response()->json([
            'message' => 'Pertemuan berhasil ditambahkan.',
            'data' => [
                'id' => $session->id,
                'session_date' => $session->session_date?->format('Y-m-d'),
                'topic' => $session->topic,
            ],
        ], 201);
    }

    public function updateSession(Request $request, Extracurricular $extracurricular, ExtracurricularSession $extracurricularSession): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ((int) $extracurricularSession->extracurricular_id !== (int) $extracurricular->id) {
            return response()->json(['message' => 'Pertemuan tidak ditemukan.'], 404);
        }

        $input = $request->all();
        foreach (['start_time', 'end_time'] as $key) {
            if (!empty($input[$key]) && is_string($input[$key])) {
                $input[$key] = substr($input[$key], 0, 5);
            }
        }

        $v = Validator::make($input, [
            'session_date' => 'sometimes|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'topic' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $extracurricularSession->update($v->validated());

        return response()->json(['message' => 'Pertemuan diperbarui.', 'data' => ['id' => $extracurricularSession->id]]);
    }

    public function destroySession(Request $request, Extracurricular $extracurricular, ExtracurricularSession $extracurricularSession): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ((int) $extracurricularSession->extracurricular_id !== (int) $extracurricular->id) {
            return response()->json(['message' => 'Pertemuan tidak ditemukan.'], 404);
        }

        $extracurricularSession->delete();

        return response()->json(['message' => 'Pertemuan dihapus.']);
    }

    private function seedAttendances(ExtracurricularSession $session, Extracurricular $extracurricular, ?int $semesterId): void
    {
        $q = ExtracurricularStudent::where('extracurricular_id', $extracurricular->id)
            ->where('status', 'aktif');
        if ($semesterId) {
            $q->where('semester_id', $semesterId);
        }
        $studentIds = $q->pluck('student_id');

        foreach ($studentIds as $studentId) {
            ExtracurricularAttendance::firstOrCreate(
                ['session_id' => $session->id, 'student_id' => $studentId],
                ['status' => 'hadir']
            );
        }
    }

    // ─── Attendances ──────────────────────────────────────────

    public function getAttendances(Request $request, Extracurricular $extracurricular, ExtracurricularSession $extracurricularSession): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ((int) $extracurricularSession->extracurricular_id !== (int) $extracurricular->id) {
            return response()->json(['message' => 'Pertemuan tidak ditemukan.'], 404);
        }

        if ($extracurricularSession->attendances()->count() === 0) {
            $this->seedAttendances($extracurricularSession, $extracurricular, $extracurricularSession->semester_id);
        }

        $rows = $extracurricularSession->attendances()
            ->with(['student' => fn ($q) => $q->with('class')])
            ->get()
            ->sortBy(fn ($a) => $a->student?->name ?? '')
            ->values()
            ->map(function (ExtracurricularAttendance $a) {
                return [
                    'id' => $a->id,
                    'student_id' => $a->student_id,
                    'status' => $a->status,
                    'notes' => $a->notes,
                    'student' => $a->student ? [
                        'id' => $a->student->id,
                        'name' => $a->student->name,
                        'nis' => $a->student->nis,
                        'class' => $this->classPayload($a->student),
                    ] : null,
                ];
            });

        return response()->json([
            'data' => $rows,
            'session' => [
                'id' => $extracurricularSession->id,
                'session_date' => $extracurricularSession->session_date?->format('Y-m-d'),
                'topic' => $extracurricularSession->topic,
            ],
            'statuses' => ExtracurricularAttendance::STATUSES,
        ]);
    }

    public function saveAttendances(Request $request, Extracurricular $extracurricular, ExtracurricularSession $extracurricularSession): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }
        if ((int) $extracurricularSession->extracurricular_id !== (int) $extracurricular->id) {
            return response()->json(['message' => 'Pertemuan tidak ditemukan.'], 404);
        }

        $v = Validator::make($request->all(), [
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|integer|exists:student,id',
            'attendances.*.status' => 'required|string|in:' . implode(',', ExtracurricularAttendance::STATUSES),
            'attendances.*.notes' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        foreach ($v->validated()['attendances'] as $row) {
            ExtracurricularAttendance::updateOrCreate(
                ['session_id' => $extracurricularSession->id, 'student_id' => $row['student_id']],
                ['status' => $row['status'], 'notes' => $row['notes'] ?? null]
            );
        }

        return response()->json(['message' => 'Kehadiran berhasil disimpan.']);
    }

    // ─── Grades ───────────────────────────────────────────────

    public function listGrades(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }

        $semesterId = $request->get('semester_id') ?? $this->activeSemesterId($extracurricular->institution_id);
        if (!$semesterId) {
            return response()->json(['message' => 'Semester aktif tidak ditemukan.'], 400);
        }

        $enrollments = ExtracurricularStudent::where('extracurricular_id', $extracurricular->id)
            ->where('semester_id', $semesterId)
            ->where('status', 'aktif')
            ->with(['student' => fn ($q) => $q->with('class')])
            ->get();

        $grades = ExtracurricularGrade::where('extracurricular_id', $extracurricular->id)
            ->where('semester_id', $semesterId)
            ->get()
            ->keyBy('student_id');

        $data = $enrollments->map(function (ExtracurricularStudent $en) use ($grades) {
            $g = $grades->get($en->student_id);

            return [
                'student_id' => $en->student_id,
                'student' => $en->student ? [
                    'id' => $en->student->id,
                    'name' => $en->student->name,
                    'nis' => $en->student->nis,
                    'class' => $this->classPayload($en->student),
                ] : null,
                'grade_id' => $g?->id,
                'score' => $g?->score !== null ? (float) $g->score : null,
                'predicate' => $g?->predicate,
                'notes' => $g?->notes,
            ];
        })->sortBy(fn ($r) => $r['student']['name'] ?? '')->values();

        return response()->json(['data' => $data, 'semester_id' => (int) $semesterId]);
    }

    public function saveGrades(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }

        $v = Validator::make($request->all(), [
            'semester_id' => 'nullable|integer|exists:semesters,id',
            'grades' => 'required|array|min:1',
            'grades.*.student_id' => 'required|integer|exists:student,id',
            'grades.*.score' => 'nullable|numeric|min:0|max:100',
            'grades.*.predicate' => 'nullable|string|max:10',
            'grades.*.notes' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return response()->json(['message' => $v->errors()->first(), 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();
        $semesterId = $data['semester_id'] ?? $this->activeSemesterId($extracurricular->institution_id);
        if (!$semesterId) {
            return response()->json(['message' => 'Semester aktif tidak ditemukan.'], 400);
        }
        $academicYearId = $this->activeAcademicYearId($extracurricular->institution_id)
            ?? $extracurricular->academic_year_id;
        $employee = ExtracurricularAccess::employeeFor($request->user());

        foreach ($data['grades'] as $row) {
            $score = array_key_exists('score', $row) ? $row['score'] : null;
            $predicate = $row['predicate'] ?? ExtracurricularGrade::predicateFromScore(
                $score !== null ? (float) $score : null
            );

            ExtracurricularGrade::updateOrCreate(
                [
                    'extracurricular_id' => $extracurricular->id,
                    'student_id' => $row['student_id'],
                    'semester_id' => $semesterId,
                ],
                [
                    'institution_id' => $extracurricular->institution_id,
                    'academic_year_id' => $academicYearId,
                    'score' => $score,
                    'predicate' => $predicate,
                    'notes' => $row['notes'] ?? null,
                    'recorded_by' => $employee?->id,
                ]
            );
        }

        return response()->json(['message' => 'Nilai berhasil disimpan.']);
    }

    // ─── Report ───────────────────────────────────────────────

    /**
     * Resolve period filters: semester | year | month.
     *
     * @return array{period:string,semester_id:?int,year:?int,month:?int,label:string,grade_semester_ids:array<int>}
     */
    private function resolveReportPeriod(Request $request, Extracurricular $extracurricular): array
    {
        $period = $request->get('period', 'semester');
        if (!in_array($period, ['semester', 'year', 'month'], true)) {
            $period = 'semester';
        }

        $semesterId = $request->filled('semester_id')
            ? (int) $request->get('semester_id')
            : null;
        $year = $request->filled('year') ? (int) $request->get('year') : (int) date('Y');
        $month = $request->filled('month') ? (int) $request->get('month') : (int) date('n');
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $gradeSemesterIds = [];
        $label = '';

        if ($period === 'year') {
            $label = 'Tahun ' . $year;
            $gradeSemesterIds = ExtracurricularSession::where('extracurricular_id', $extracurricular->id)
                ->whereYear('session_date', $year)
                ->whereNotNull('semester_id')
                ->distinct()
                ->pluck('semester_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } elseif ($period === 'month') {
            $label = ($monthNames[$month] ?? $month) . ' ' . $year;
            $gradeSemesterIds = ExtracurricularSession::where('extracurricular_id', $extracurricular->id)
                ->whereYear('session_date', $year)
                ->whereMonth('session_date', $month)
                ->whereNotNull('semester_id')
                ->distinct()
                ->pluck('semester_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        } else {
            if (!$semesterId) {
                $semesterId = $this->activeSemesterId($extracurricular->institution_id);
            }
            $semester = $semesterId ? Semester::with('academicYear:id,name')->find($semesterId) : null;
            $label = $semester
                ? trim(($semester->name ?? 'Semester') . ($semester->academicYear?->name ? ' · ' . $semester->academicYear->name : ''))
                : 'Semua semester';
            if ($semesterId) {
                $gradeSemesterIds = [$semesterId];
            }
        }

        return [
            'period' => $period,
            'semester_id' => $semesterId,
            'year' => $year,
            'month' => $month,
            'label' => $label,
            'grade_semester_ids' => $gradeSemesterIds,
        ];
    }

    private function applySessionPeriodFilter($query, array $period): void
    {
        if ($period['period'] === 'year') {
            $query->whereYear('session_date', $period['year']);
        } elseif ($period['period'] === 'month') {
            $query->whereYear('session_date', $period['year'])
                ->whereMonth('session_date', $period['month']);
        } elseif (!empty($period['semester_id'])) {
            $query->where('semester_id', $period['semester_id']);
        }
    }

    /**
     * Build full report payload shared by JSON / CSV / PDF.
     */
    private function buildReportData(Request $request, Extracurricular $extracurricular): array
    {
        $period = $this->resolveReportPeriod($request, $extracurricular);

        $sessionsQuery = ExtracurricularSession::where('extracurricular_id', $extracurricular->id)
            ->orderBy('session_date')
            ->orderBy('id');
        $this->applySessionPeriodFilter($sessionsQuery, $period);
        $sessions = $sessionsQuery->withCount('attendances')->get();
        $sessionIds = $sessions->pluck('id');
        $sessionCount = $sessionIds->count();

        $participantsQuery = ExtracurricularStudent::where('extracurricular_id', $extracurricular->id)
            ->where('status', 'aktif');
        if ($period['period'] === 'semester' && $period['semester_id']) {
            $participantsQuery->where('semester_id', $period['semester_id']);
        } elseif (!empty($period['grade_semester_ids'])) {
            $participantsQuery->whereIn('semester_id', $period['grade_semester_ids']);
        }
        $participantCount = $participantsQuery->count();

        $attendanceStats = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
            'total' => 0,
            'hadir_pct' => null,
        ];
        if ($sessionIds->isNotEmpty()) {
            $counts = ExtracurricularAttendance::whereIn('session_id', $sessionIds)
                ->select('status', DB::raw('COUNT(*) as c'))
                ->groupBy('status')
                ->pluck('c', 'status');
            foreach (ExtracurricularAttendance::STATUSES as $st) {
                $attendanceStats[$st] = (int) ($counts[$st] ?? 0);
            }
            $attendanceStats['total'] = array_sum(array_intersect_key($attendanceStats, array_flip(ExtracurricularAttendance::STATUSES)));
            if ($attendanceStats['total'] > 0) {
                $attendanceStats['hadir_pct'] = round(($attendanceStats['hadir'] / $attendanceStats['total']) * 100, 1);
            }
        }

        $gradesQuery = ExtracurricularGrade::where('extracurricular_id', $extracurricular->id)
            ->whereNotNull('score');
        if (!empty($period['grade_semester_ids'])) {
            $gradesQuery->whereIn('semester_id', $period['grade_semester_ids']);
        } elseif ($period['period'] === 'semester' && $period['semester_id']) {
            $gradesQuery->where('semester_id', $period['semester_id']);
        }
        $avgScore = (clone $gradesQuery)->avg('score');
        $gradedCount = (clone $gradesQuery)->count();

        $gradeMapQuery = ExtracurricularGrade::where('extracurricular_id', $extracurricular->id);
        if (!empty($period['grade_semester_ids'])) {
            $gradeMapQuery->whereIn('semester_id', $period['grade_semester_ids']);
        } elseif ($period['period'] === 'semester' && $period['semester_id']) {
            $gradeMapQuery->where('semester_id', $period['semester_id']);
        }
        $gradeMap = $gradeMapQuery->get()->keyBy('student_id');

        // Raw attendance cells: [student_id][session_id] => status
        $attendanceCells = [];
        if ($sessionIds->isNotEmpty()) {
            $cellRows = ExtracurricularAttendance::whereIn('session_id', $sessionIds)
                ->get(['student_id', 'session_id', 'status']);
            foreach ($cellRows as $cell) {
                $attendanceCells[(int) $cell->student_id][(int) $cell->session_id] = $cell->status;
            }
        }

        $perStudent = [];
        if ($sessionIds->isNotEmpty()) {
            $rows = ExtracurricularAttendance::whereIn('session_id', $sessionIds)
                ->select('student_id', 'status', DB::raw('COUNT(*) as c'))
                ->groupBy('student_id', 'status')
                ->get();
            $byStudent = [];
            foreach ($rows as $r) {
                $byStudent[$r->student_id][$r->status] = (int) $r->c;
            }
            $students = Student::whereIn('id', array_keys($byStudent))->with('class')->get()->keyBy('id');

            foreach ($byStudent as $studentId => $stMap) {
                $student = $students->get($studentId);
                $hadir = $stMap['hadir'] ?? 0;
                $total = array_sum($stMap);
                $g = $gradeMap->get($studentId);
                $perStudent[] = [
                    'student_id' => $studentId,
                    'name' => $student?->name,
                    'nis' => $student?->nis,
                    'nisn' => $student?->nisn,
                    'class' => $this->classPayload($student),
                    'hadir' => $hadir,
                    'izin' => $stMap['izin'] ?? 0,
                    'sakit' => $stMap['sakit'] ?? 0,
                    'alpha' => $stMap['alpha'] ?? 0,
                    'total_sessions' => $total,
                    'hadir_pct' => $total > 0 ? round(($hadir / $total) * 100, 1) : null,
                    'score' => $g?->score !== null ? (float) $g->score : null,
                    'predicate' => $g?->predicate,
                    'notes' => $g?->notes,
                ];
            }
            usort($perStudent, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        }

        $sessionList = $sessions->map(function (ExtracurricularSession $s) {
            return [
                'id' => $s->id,
                'session_date' => $s->session_date?->format('Y-m-d'),
                'start_time' => $s->start_time?->format('H:i'),
                'end_time' => $s->end_time?->format('H:i'),
                'topic' => $s->topic,
                'notes' => $s->notes,
                'attendances_count' => $s->attendances_count,
            ];
        })->values()->all();

        // Attendance matrix: peserta × setiap pertemuan (status per tanggal)
        $matrixSessions = $sessions->map(function (ExtracurricularSession $s) {
            $date = $s->session_date;
            return [
                'id' => $s->id,
                'session_date' => $date?->format('Y-m-d'),
                'label' => $date ? $date->format('j') : (string) $s->id,
                'label_short' => $date ? $date->locale('id')->isoFormat('D MMM') : '—',
                'topic' => $s->topic,
            ];
        })->values()->all();

        $enrolled = (clone $participantsQuery)->get();
        $matrixStudentIds = collect($enrolled->pluck('student_id'))
            ->merge(array_keys($attendanceCells))
            ->unique()
            ->filter()
            ->values();

        $matrixStudents = Student::whereIn('id', $matrixStudentIds)->with('class')->get()->keyBy('id');
        $matrixRows = [];
        foreach ($matrixStudentIds as $studentId) {
            $student = $matrixStudents->get($studentId);
            if (!$student) {
                continue;
            }
            $statuses = [];
            $counts = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0];
            foreach ($sessions as $s) {
                $st = $attendanceCells[(int) $studentId][(int) $s->id] ?? null;
                $statuses[(string) $s->id] = $st;
                if ($st && isset($counts[$st])) {
                    $counts[$st]++;
                }
            }
            $marked = array_sum($counts);
            $matrixRows[] = [
                'student_id' => (int) $studentId,
                'name' => $student->name,
                'nis' => $student->nis,
                'class' => $this->classPayload($student),
                'statuses' => $statuses,
                'hadir' => $counts['hadir'],
                'izin' => $counts['izin'],
                'sakit' => $counts['sakit'],
                'alpha' => $counts['alpha'],
                'hadir_pct' => $marked > 0 ? round(($counts['hadir'] / $marked) * 100, 1) : null,
            ];
        }
        usort($matrixRows, fn ($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

        $extracurricular->loadMissing(['supervisor:id,name', 'institution', 'room:id,name']);

        return [
            'extracurricular' => [
                'id' => $extracurricular->id,
                'name' => $extracurricular->name,
                'description' => $extracurricular->description,
                'supervisor' => $extracurricular->supervisor
                    ? ['id' => $extracurricular->supervisor->id, 'name' => $extracurricular->supervisor->name]
                    : null,
                'status' => $extracurricular->status,
            ],
            'period' => [
                'type' => $period['period'],
                'semester_id' => $period['semester_id'],
                'year' => $period['year'],
                'month' => $period['month'],
                'label' => $period['label'],
            ],
            'participants_count' => $participantCount,
            'sessions_count' => $sessionCount,
            'attendance' => $attendanceStats,
            'grades' => [
                'graded_count' => $gradedCount,
                'average_score' => $avgScore !== null ? round((float) $avgScore, 2) : null,
            ],
            'sessions' => $sessionList,
            'per_student' => $perStudent,
            'attendance_matrix' => [
                'sessions' => $matrixSessions,
                'rows' => $matrixRows,
            ],
            'semester_id' => $period['semester_id'],
        ];
    }

    public function report(Request $request, Extracurricular $extracurricular): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }

        try {
            return response()->json(['data' => $this->buildReportData($request, $extracurricular)]);
        } catch (\Throwable $e) {
            Log::error('Extracurricular report failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memuat laporan ekstrakurikuler.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function exportReportCsv(Request $request, Extracurricular $extracurricular): StreamedResponse|JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }

        $data = $this->buildReportData($request, $extracurricular);
        $rows = $data['per_student'] ?? [];
        $periodLabel = $data['period']['label'] ?? '';
        $matrix = $data['attendance_matrix'] ?? ['sessions' => [], 'rows' => []];
        $matrixSessions = $matrix['sessions'] ?? [];
        $matrixRows = $matrix['rows'] ?? [];

        $filename = 'laporan-ekskul-' . preg_replace('/\s+/', '-', strtolower($extracurricular->name)) . '-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows, $periodLabel, $data, $matrixSessions, $matrixRows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['Laporan Ekstrakurikuler', $data['extracurricular']['name'] ?? '']);
            fputcsv($out, ['Periode', $periodLabel]);
            fputcsv($out, ['Peserta', $data['participants_count'] ?? 0]);
            fputcsv($out, ['Pertemuan', $data['sessions_count'] ?? 0]);
            fputcsv($out, []);

            if (!empty($matrixSessions) && !empty($matrixRows)) {
                fputcsv($out, ['REKAP KEHADIRAN PER PERTEMUAN']);
                $header = ['No', 'Nama', 'NIS', 'Kelas'];
                foreach ($matrixSessions as $s) {
                    $header[] = $s['label_short'] ?? $s['session_date'] ?? '';
                }
                $header[] = 'H';
                $header[] = 'I';
                $header[] = 'S';
                $header[] = 'A';
                $header[] = '% Hadir';
                fputcsv($out, $header);
                $i = 1;
                $code = ['hadir' => 'H', 'izin' => 'I', 'sakit' => 'S', 'alpha' => 'A'];
                foreach ($matrixRows as $r) {
                    $line = [
                        $i++,
                        $r['name'] ?? '',
                        $r['nis'] ?? '',
                        $r['class']['name'] ?? '',
                    ];
                    foreach ($matrixSessions as $s) {
                        $st = $r['statuses'][(string) $s['id']] ?? null;
                        $line[] = $st ? ($code[$st] ?? $st) : '-';
                    }
                    $line[] = $r['hadir'] ?? 0;
                    $line[] = $r['izin'] ?? 0;
                    $line[] = $r['sakit'] ?? 0;
                    $line[] = $r['alpha'] ?? 0;
                    $line[] = $r['hadir_pct'] ?? '';
                    fputcsv($out, $line);
                }
                fputcsv($out, []);
                fputcsv($out, ['Keterangan: H=Hadir, I=Izin, S=Sakit, A=Alpha, -=Tidak ada data']);
                fputcsv($out, []);
            }

            fputcsv($out, ['REKAP RINGKAS PER SISWA']);
            fputcsv($out, ['No', 'Nama', 'NIS', 'Kelas', 'Hadir', 'Izin', 'Sakit', 'Alpha', '% Hadir', 'Nilai', 'Predikat']);
            $i = 1;
            foreach ($rows as $r) {
                fputcsv($out, [
                    $i++,
                    $r['name'] ?? '',
                    $r['nis'] ?? '',
                    $r['class']['name'] ?? '',
                    $r['hadir'] ?? 0,
                    $r['izin'] ?? 0,
                    $r['sakit'] ?? 0,
                    $r['alpha'] ?? 0,
                    $r['hadir_pct'] ?? '',
                    $r['score'] ?? '',
                    $r['predicate'] ?? '',
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportReportPdf(Request $request, Extracurricular $extracurricular)
    {
        if ($resp = $this->denyUnlessCanAccess($request, $extracurricular)) {
            return $resp;
        }

        try {
            $data = $this->buildReportData($request, $extracurricular);
            $institution = Institution::find($extracurricular->institution_id);
            $user = $request->user();
            $matrixSessions = $data['attendance_matrix']['sessions'] ?? [];
            $orientation = count($matrixSessions) > 4 ? 'landscape' : 'portrait';

            $pdf = DomPDF::loadView('extracurricular.report', [
                'data' => $data,
                'institution' => $institution,
                'printedBy' => $user?->name,
                'printedAt' => now()->locale('id')->isoFormat('D MMMM YYYY HH:mm'),
            ])->setPaper('a4', $orientation);

            $slug = preg_replace('/\s+/', '-', strtolower($extracurricular->name));
            $filename = 'laporan_ekskul_' . $slug . '_' . now()->format('Ymd') . '.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Throwable $e) {
            Log::error('Extracurricular report PDF failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencetak laporan PDF.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
