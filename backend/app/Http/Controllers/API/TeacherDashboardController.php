<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassResource;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\StudentResource;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\PiketLog;
use App\Models\PiketSchedule;
use App\Models\SchoolClass;
use App\Models\TeachingJournal;
use App\Support\InstitutionContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TeacherDashboardController extends Controller
{
    /**
     * Get teacher dashboard summary.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user || !$user->isTeacherOrStaff()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $user->load(['teacherProfile', 'employeeProfile']);
            $teacher = $user->teacherProfile ?? $user->employeeProfile;

            if (!$teacher) {
                return response()->json(['message' => 'Profil guru tidak ditemukan'], 404);
            }

            $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
            $activeAcademicYear = null;
            $activeSemesterId = null;

            if ($institutionId) {
                $institution = Institution::find($institutionId);
                if ($institution) {
                    if ($institution->active_academic_year_id) {
                        $activeAcademicYear = AcademicYear::find($institution->active_academic_year_id);
                    }
                    $activeSemesterId = $institution->active_semester_id;
                }
            }

            // Homeroom (wali kelas) + classes taught via lesson schedule
            $homeroomQuery = SchoolClass::query()->where('teacher_id', $teacher->id);
            if ($institutionId) {
                $homeroomQuery->where('institution_id', $institutionId);
            }
            if ($activeAcademicYear) {
                $homeroomQuery->where('academic_year_id', $activeAcademicYear->id);
            }
            $homeroomIds = $homeroomQuery->pluck('id');

            $scheduleClassQuery = LessonSchedule::query()
                ->where('employee_id', $teacher->id)
                ->when($institutionId, fn ($q) => $q->where('institution_id', $institutionId));

            if ($activeSemesterId) {
                $scheduleClassQuery->where('semester_id', $activeSemesterId);
            }

            $scheduleClassIds = $scheduleClassQuery->distinct()->pluck('class_id');
            $allClassIds = $homeroomIds->merge($scheduleClassIds)->unique()->values();

            $classes = collect();
            if ($allClassIds->isNotEmpty()) {
                $classes = SchoolClass::withCount('students')
                    ->with([
                        'room:id,name,code',
                        'academicYear:id,name,code',
                    ])
                    ->whereIn('id', $allClassIds)
                    ->orderBy('grade')
                    ->orderBy('name')
                    ->get()
                    ->map(function (SchoolClass $class) use ($homeroomIds) {
                        $class->setAttribute('is_homeroom', $homeroomIds->contains($class->id));

                        return $class;
                    });
            }

            $totalStudents = $classes->sum('students_count');
            $homeroomCount = $classes->where('is_homeroom', true)->count();
            $taughtCount = $classes->count();

            $jurnalThisWeekCount = 0;
            $gradesPending = [];

            if ($activeSemesterId && $institutionId) {
                $startOfWeek = Carbon::now()->startOfWeek();
                $endOfWeek = Carbon::now()->endOfWeek();
                $jurnalThisWeekCount = TeachingJournal::where('employee_id', $teacher->id)
                    ->where('institution_id', $institutionId)
                    ->whereBetween('journal_date', [$startOfWeek, $endOfWeek])
                    ->count();

                $schedules = LessonSchedule::with(['schoolClass:id,name', 'subject:id,name'])
                    ->where('employee_id', $teacher->id)
                    ->where('institution_id', $institutionId)
                    ->where('semester_id', $activeSemesterId)
                    ->get();

                $seenPairs = [];
                foreach ($schedules as $s) {
                    $pairKey = $s->class_id . '-' . $s->subject_id;
                    if (isset($seenPairs[$pairKey])) {
                        continue;
                    }
                    $seenPairs[$pairKey] = true;

                    $studentCount = SchoolClass::find($s->class_id)?->students()->count() ?? 0;
                    $gradeCount = Grade::where('class_id', $s->class_id)
                        ->where('subject_id', $s->subject_id)
                        ->where('semester_id', $activeSemesterId)
                        ->distinct()
                        ->count('student_id');
                    if ($studentCount > 0 && $gradeCount < $studentCount) {
                        $gradesPending[] = [
                            'class_id' => $s->class_id,
                            'class_name' => $s->schoolClass?->name ?? '',
                            'subject_id' => $s->subject_id,
                            'subject_name' => $s->subject?->name ?? '',
                            'semester_id' => $activeSemesterId,
                        ];
                    }
                }
            }

            $classesPayload = $classes->map(function (SchoolClass $class) {
                $resource = (new ClassResource($class))->resolve();
                $resource['is_homeroom'] = (bool) $class->getAttribute('is_homeroom');

                return $resource;
            })->values();

            $piketToday = $this->resolvePiketToday($teacher->id, $institutionId);

            return response()->json([
                'data' => [
                    'teacher' => new EmployeeResource($teacher->load('institution')),
                    'summary' => [
                        'total_classes' => $taughtCount,
                        'total_students' => $totalStudents,
                        'homeroom_classes' => $homeroomCount,
                    ],
                    'classes' => $classesPayload,
                    'active_academic_year' => $activeAcademicYear ? [
                        'id' => $activeAcademicYear->id,
                        'name' => $activeAcademicYear->name,
                        'code' => $activeAcademicYear->code,
                    ] : null,
                    'jurnal_this_week_count' => $jurnalThisWeekCount,
                    'grades_pending' => $gradesPending,
                    'piket_today' => $piketToday,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load teacher dashboard', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memuat dashboard guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Daftar siswa kelas wali (hanya kelas di mana guru adalah wali kelas).
     */
    public function classStudents(Request $request, int $id)
    {
        $user = $request->user();

        if (!$user || !$user->isTeacherOrStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->load(['teacherProfile', 'employeeProfile']);
        $teacher = $user->teacherProfile ?? $user->employeeProfile;

        if (!$teacher) {
            return response()->json(['message' => 'Profil guru tidak ditemukan'], 404);
        }

        $class = SchoolClass::findOrFail($id);

        if ((int) $class->teacher_id !== (int) $teacher->id) {
            return response()->json([
                'message' => 'Anda hanya dapat melihat siswa pada kelas yang Anda waliki.',
            ], 403);
        }

        if (!InstitutionContext::canAccessInstitution($user, (int) $class->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $perPage = min((int) $request->get('per_page', 100), 200);
        $query = $class->students()->orderBy('name', 'asc');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        } else {
            $query->where('status', 'active');
        }

        $students = $query->paginate($perPage);

        return StudentResource::collection($students)->additional([
            'class' => [
                'id' => $class->id,
                'name' => $class->name,
                'grade' => $class->grade,
                'academic_year' => $class->academic_year,
            ],
        ]);
    }

    /**
     * Status piket guru untuk hari ini (tanpa wajib modul guru_piket).
     */
    protected function resolvePiketToday(int $employeeId, ?int $institutionId): array
    {
        $empty = [
            'is_on_duty' => false,
            'date' => now()->toDateString(),
            'day_of_week' => now()->dayOfWeekIso,
            'day_name' => PiketSchedule::DAYS[now()->dayOfWeekIso] ?? now()->translatedFormat('l'),
            'schedule' => null,
            'has_log' => false,
            'log_status' => null,
        ];

        if (!$institutionId || !Schema::hasTable('piket_schedules')) {
            return $empty;
        }

        try {
            $today = Carbon::today();
            $day = $today->dayOfWeekIso;

            $schedule = PiketSchedule::query()
                ->where('institution_id', $institutionId)
                ->where('employee_id', $employeeId)
                ->where('day_of_week', $day)
                ->orderBy('shift')
                ->first();

            if (!$schedule) {
                return $empty;
            }

            $log = null;
            if (Schema::hasTable('piket_logs')) {
                $log = PiketLog::query()
                    ->where('institution_id', $institutionId)
                    ->where('employee_id', $employeeId)
                    ->whereDate('duty_date', $today->toDateString())
                    ->first();
            }

            return [
                'is_on_duty' => true,
                'date' => $today->toDateString(),
                'day_of_week' => $day,
                'day_name' => PiketSchedule::DAYS[$day] ?? $today->translatedFormat('l'),
                'schedule' => [
                    'id' => $schedule->id,
                    'shift' => $schedule->shift,
                    'shift_label' => PiketSchedule::SHIFTS[$schedule->shift] ?? $schedule->shift,
                    'start_time' => $schedule->start_time?->format('H:i'),
                    'end_time' => $schedule->end_time?->format('H:i'),
                    'notes' => $schedule->notes,
                ],
                'has_log' => (bool) $log,
                'log_status' => $log?->status,
            ];
        } catch (\Throwable $e) {
            Log::warning('Teacher dashboard piket_today skipped', ['error' => $e->getMessage()]);

            return $empty;
        }
    }
}
