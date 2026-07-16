<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLessonScheduleRequest;
use App\Http\Requests\UpdateLessonScheduleRequest;
use App\Http\Requests\CopyLessonScheduleRequest;
use App\Http\Resources\LessonScheduleResource;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Services\LessonScheduleService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class LessonScheduleController extends Controller
{
    public function __construct(
        protected LessonScheduleService $lessonScheduleService
    ) {}

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    private function canAccessSchedule($user, LessonSchedule $schedule): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return InstitutionContext::canAccessInstitution($user, (int) $schedule->institution_id);
    }

    /**
     * List lesson schedules with filters.
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['semester_id', 'class_id', 'employee_id', 'day_of_week', 'subject_id', 'room_id']);
            if (!isset($filters['semester_id']) || $filters['semester_id'] === '') {
                $institution = Institution::find($institutionId);
                if ($institution && $institution->active_semester_id) {
                    $filters['semester_id'] = $institution->active_semester_id;
                }
            }
            $perPage = min($request->get('per_page', 50), 100);
            $schedules = $this->lessonScheduleService->listForInstitution($institutionId, $filters, $perPage);

            return LessonScheduleResource::collection($schedules);
        } catch (\Exception $e) {
            Log::error('LessonSchedule index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data jadwal pelajaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Store a new lesson schedule.
     */
    public function store(StoreLessonScheduleRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $schedule = $this->lessonScheduleService->create($institutionId, $request->validated());
            return (new LessonScheduleResource($schedule))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Semester, kelas, mata pelajaran, atau guru tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('LessonSchedule store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan jadwal pelajaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single lesson schedule.
     */
    public function show(Request $request, LessonSchedule $lesson_schedule): LessonScheduleResource|JsonResponse
    {
        $user = $request->user();
        if (!$this->canAccessSchedule($user, $lesson_schedule)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $lesson_schedule->load(['semester', 'schoolClass', 'subject', 'employee', 'room']);
        return new LessonScheduleResource($lesson_schedule);
    }

    /**
     * Update lesson schedule.
     */
    public function update(UpdateLessonScheduleRequest $request, LessonSchedule $lesson_schedule): LessonScheduleResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$this->canAccessSchedule($user, $lesson_schedule)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $schedule = $this->lessonScheduleService->update($lesson_schedule, $request->validated());
            return new LessonScheduleResource($schedule);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('LessonSchedule update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui jadwal pelajaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete lesson schedule.
     */
    public function destroy(Request $request, LessonSchedule $lesson_schedule): JsonResponse
    {
        $user = $request->user();
        if (!$this->canAccessSchedule($user, $lesson_schedule)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lesson_schedule->delete();
        return response()->json(['message' => 'Jadwal pelajaran berhasil dihapus.']);
    }

    /**
     * Get schedule by class (matrix day x period).
     */
    public function byClass(Request $request, int $classId): JsonResponse
    {
        $user = $request->user();
        $institutionId = $this->resolveInstitutionId($request);
        if ($user->isStudent()) {
            $profile = $user->studentProfile;
            if (!$profile || (int) $profile->class_id !== (int) $classId) {
                return response()->json(['message' => 'Anda hanya dapat melihat jadwal kelas sendiri.'], 403);
            }
            $institutionId = $profile->institution_id;
        }
        $semesterId = $request->get('semester_id');
        if (!$institutionId || !$semesterId) {
            return response()->json(['message' => 'Institusi atau semester tidak ditemukan.'], 403);
        }

        $data = $this->lessonScheduleService->getByClass($classId, (int) $semesterId, $institutionId);
        return response()->json($data);
    }

    /**
     * Get schedule by teacher (employee).
     */
    public function byTeacher(Request $request, int $employeeId): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        $semesterId = $request->get('semester_id');
        if (!$institutionId || !$semesterId) {
            return response()->json(['message' => 'Institusi atau semester tidak ditemukan.'], 403);
        }

        $schedules = $this->lessonScheduleService->getByTeacher($employeeId, (int) $semesterId, $institutionId);
        return response()->json(['data' => LessonScheduleResource::collection($schedules)]);
    }

    /**
     * Get schedule by room.
     */
    public function byRoom(Request $request, int $roomId): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        $semesterId = $request->get('semester_id');
        if (!$institutionId || !$semesterId) {
            return response()->json(['message' => 'Institusi atau semester tidak ditemukan.'], 403);
        }

        $schedules = $this->lessonScheduleService->getByRoom($roomId, (int) $semesterId, $institutionId);
        return response()->json(['data' => LessonScheduleResource::collection($schedules)]);
    }

    /**
     * Copy schedules from source semester to target.
     */
    public function copySemester(CopyLessonScheduleRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $count = $this->lessonScheduleService->copySemester(
                $institutionId,
                $request->validated()['source_semester_id'],
                $request->validated()['target_semester_id'],
                $request->validated()['class_id'] ?? null
            );

            return response()->json([
                'message' => "Jadwal berhasil disalin. {$count} slot ditambahkan.",
                'copied_count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error('LessonSchedule copySemester failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyalin jadwal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete all schedules for a class in a semester.
     */
    public function deleteByClass(Request $request, int $classId): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        $semesterId = $request->get('semester_id');
        if (!$institutionId || !$semesterId) {
            return response()->json(['message' => 'Semester wajib dipilih (semester_id).'], 422);
        }

        $count = $this->lessonScheduleService->deleteByClass($classId, (int) $semesterId, $institutionId);
        return response()->json([
            'message' => "Jadwal kelas berhasil dihapus. {$count} slot dihapus.",
            'deleted_count' => $count,
        ]);
    }

    /**
     * Delete all schedules for a semester.
     */
    public function deleteBySemester(Request $request, int $semesterId): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $count = $this->lessonScheduleService->deleteBySemester($semesterId, $institutionId);
        return response()->json([
            'message' => "Jadwal semester berhasil dihapus. {$count} slot dihapus.",
            'deleted_count' => $count,
        ]);
    }
}
