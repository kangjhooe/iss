<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\AcademicCalendarEventResource;
use App\Http\Resources\ViolationResource;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\Student;
use App\Services\AcademicCalendarService;
use App\Services\GradeService;
use App\Services\StudentAttendanceService;
use App\Services\ViolationService;
use App\Support\ParentAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class ParentPortalController extends Controller
{
    public function __construct(
        protected GradeService $gradeService,
        protected StudentAttendanceService $studentAttendanceService,
        protected ViolationService $violationService,
        protected AcademicCalendarService $academicCalendarService,
    ) {}

    public function children(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isParent()) {
            return response()->json(['message' => 'Hanya orang tua yang dapat mengakses.'], 403);
        }

        $children = ParentAccess::linkedStudents($user)->map(function (Student $s) {
            return [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'nisn' => $s->nisn,
                'class_id' => $s->class_id,
                'class_name' => $s->schoolClass?->name,
                'status' => $s->status,
                'institution_id' => $s->institution_id,
                'institution_name' => $s->institution?->name,
            ];
        });

        return response()->json(['data' => $children]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isParent()) {
            return response()->json(['message' => 'Hanya orang tua yang dapat mengakses.'], 403);
        }

        $children = ParentAccess::linkedStudents($user);
        $institutionId = $children->first()?->institution_id ?? $user->institution_id;
        $announcements = [];
        if ($institutionId) {
            $announcements = $this->academicCalendarService->getUpcoming((int) $institutionId, 30)
                ->take(5)
                ->values();
        }

        return response()->json([
            'data' => [
                'children' => $children->map(fn (Student $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'class_name' => $s->schoolClass?->name,
                    'institution_name' => $s->institution?->name,
                ]),
                'announcements' => AcademicCalendarEventResource::collection($announcements)->resolve(),
            ],
        ]);
    }

    public function schedule(Request $request, int $studentId): JsonResponse
    {
        $student = $this->authorizeChild($request, $studentId);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        if (!$student->class_id) {
            return response()->json(['data' => [], 'message' => 'Siswa belum memiliki kelas.']);
        }

        $schedules = LessonSchedule::query()
            ->with(['subject:id,name', 'room:id,name', 'employee:id,name'])
            ->where('class_id', $student->class_id)
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'day_of_week' => $s->day_of_week,
                'period' => $s->period,
                'start_time' => $s->start_time,
                'end_time' => $s->end_time,
                'subject' => $s->subject ? ['id' => $s->subject->id, 'name' => $s->subject->name] : null,
                'room' => $s->room ? ['id' => $s->room->id, 'name' => $s->room->name] : null,
                'teacher' => $s->employee ? ['id' => $s->employee->id, 'name' => $s->employee->name] : null,
            ]);

        return response()->json([
            'data' => $schedules,
            'meta' => [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'class_id' => $student->class_id,
                'class_name' => $student->schoolClass?->name,
            ],
        ]);
    }

    public function grades(Request $request, int $studentId): JsonResponse
    {
        $student = $this->authorizeChild($request, $studentId);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        $semesterId = (int) ($request->get('semester_id') ?: 0);
        if (!$semesterId) {
            $semesterId = (int) (Institution::find($student->institution_id)?->active_semester_id ?: 0);
        }

        if (!$semesterId) {
            return response()->json(['data' => [], 'message' => 'Semester aktif tidak ditemukan.']);
        }

        try {
            $rows = $this->gradeService->getByStudentSemester(
                (int) $student->institution_id,
                $student->id,
                $semesterId
            );

            return response()->json([
                'data' => $rows,
                'meta' => [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'semester_id' => $semesterId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Parent grades failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil nilai.'], 500);
        }
    }

    public function attendance(Request $request, int $studentId): JsonResponse
    {
        $student = $this->authorizeChild($request, $studentId);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        try {
            $semesterId = $request->integer('semester_id') ?: null;
            $history = $this->studentAttendanceService->historyForStudent(
                $student->id,
                (int) $student->institution_id,
                $semesterId,
                $request->query('date_from') ?: null,
                $request->query('date_to') ?: null
            );

            $summary = [
                'total' => $history->count(),
                'hadir' => $history->where('status', 'Hadir')->count(),
                'izin' => $history->where('status', 'Izin')->count(),
                'sakit' => $history->where('status', 'Sakit')->count(),
                'alpha' => $history->where('status', 'Alpha')->count(),
            ];

            return response()->json([
                'data' => $history,
                'meta' => [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'summary' => $summary,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Parent attendance failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil absensi.'], 500);
        }
    }

    public function violations(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        $student = $this->authorizeChild($request, $studentId);
        if ($student instanceof JsonResponse) {
            return $student;
        }

        try {
            $violations = $this->violationService->listByStudent($student->id, (int) $student->institution_id);

            return ViolationResource::collection($violations)->additional([
                'meta' => [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Parent violations failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil pelanggaran.'], 500);
        }
    }

    public function announcements(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isParent()) {
            return response()->json(['message' => 'Hanya orang tua yang dapat mengakses.'], 403);
        }

        $children = ParentAccess::linkedStudents($user);
        $institutionId = $children->first()?->institution_id ?? $user->institution_id;
        if (!$institutionId) {
            return AcademicCalendarEventResource::collection(collect());
        }

        $days = min((int) $request->get('days', 60), 90);
        $events = $this->academicCalendarService->getUpcoming((int) $institutionId, $days);

        return AcademicCalendarEventResource::collection($events);
    }

    /**
     * @return Student|JsonResponse
     */
    protected function authorizeChild(Request $request, int $studentId)
    {
        $user = $request->user();
        if (!$user || !$user->isParent()) {
            return response()->json(['message' => 'Hanya orang tua yang dapat mengakses.'], 403);
        }

        if (!ParentAccess::canAccessStudent($user, $studentId)) {
            return response()->json(['message' => 'Anda tidak memiliki akses ke data siswa ini.'], 403);
        }

        $student = Student::with(['schoolClass:id,name', 'institution:id,name'])->find($studentId);
        if (!$student) {
            return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
        }

        return $student;
    }
}
