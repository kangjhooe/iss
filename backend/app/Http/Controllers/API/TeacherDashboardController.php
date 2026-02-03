<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassResource;
use App\Http\Resources\EmployeeResource;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\SchoolClass;
use App\Models\TeachingJournal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

            $institutionId = $teacher->institution_id ?? $user->institution_id;
            $activeAcademicYear = null;

            if ($institutionId) {
                $institution = Institution::find($institutionId);
                if ($institution && $institution->active_academic_year_id) {
                    $activeAcademicYear = AcademicYear::find($institution->active_academic_year_id);
                }
            }

            $classesQuery = SchoolClass::withCount('students')
                ->with([
                    'room:id,name,code',
                    'academicYear:id,name,code',
                ])
                ->where('teacher_id', $teacher->id);

            if ($institutionId) {
                $classesQuery->where('institution_id', $institutionId);
            }

            if ($activeAcademicYear) {
                $classesQuery->where('academic_year_id', $activeAcademicYear->id);
            }

            $classes = $classesQuery
                ->orderBy('grade')
                ->orderBy('name')
                ->get();

            $totalStudents = $classes->sum('students_count');

            $jurnalThisWeekCount = 0;
            $gradesPending = [];
            $activeSemesterId = null;
            if ($institutionId) {
                $institution = Institution::find($institutionId);
                if ($institution && $institution->active_semester_id) {
                    $activeSemesterId = $institution->active_semester_id;
                }
            }
            if ($activeSemesterId) {
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
                foreach ($schedules as $s) {
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

            return response()->json([
                'data' => [
                    'teacher' => new EmployeeResource($teacher->load('institution')),
                    'summary' => [
                        'total_classes' => $classes->count(),
                        'total_students' => $totalStudents,
                    ],
                    'classes' => ClassResource::collection($classes),
                    'active_academic_year' => $activeAcademicYear ? [
                        'id' => $activeAcademicYear->id,
                        'name' => $activeAcademicYear->name,
                        'code' => $activeAcademicYear->code,
                    ] : null,
                    'jurnal_this_week_count' => $jurnalThisWeekCount,
                    'grades_pending' => $gradesPending,
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
}
