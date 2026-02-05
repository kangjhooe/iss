<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\UpdateAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\AchievementType;
use App\Models\Institution;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AchievementController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $academicYearId = $request->get('academic_year_id');
            $semesterId = $request->get('semester_id');
            $institution = Institution::find($institutionId);
            if ($institution) {
                if (!$academicYearId && $institution->active_academic_year_id) {
                    $academicYearId = $institution->active_academic_year_id;
                }
                if (!$semesterId && $institution->active_semester_id) {
                    $semesterId = $institution->active_semester_id;
                }
            }

            $query = Achievement::with(['student:id,name,nis,nisn', 'achievementType:id,name,point_value', 'giver:id,name', 'academicYear:id,name,code', 'semester:id,name'])
                ->forInstitution($institutionId)
                ->orderBy('achievement_date', 'desc');

            if ($academicYearId) {
                $query->where('academic_year_id', $academicYearId);
            }
            if ($semesterId) {
                $query->where('semester_id', $semesterId);
            }
            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }
            if ($request->filled('achievement_type_id')) {
                $query->where('achievement_type_id', $request->achievement_type_id);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('achievement_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('achievement_date', '<=', $request->date_to);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);
            return AchievementResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Achievement index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data prestasi.'], 500);
        }
    }

    public function store(StoreAchievementRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $student = Student::where('id', $request->student_id)->where('institution_id', $institutionId)->firstOrFail();
            $type = AchievementType::where('id', $request->achievement_type_id)->where('institution_id', $institutionId)->where('is_active', true)->firstOrFail();

            $pointValue = $request->input('point_value', $type->point_value);

            $achievement = Achievement::create([
                'institution_id' => $institutionId,
                'student_id' => $student->id,
                'achievement_type_id' => $type->id,
                'given_by' => $user->id,
                'achievement_date' => $request->achievement_date,
                'point_value' => $pointValue,
                'notes' => $request->notes,
                'academic_year_id' => $student->academic_year_id,
                'semester_id' => $student->semester_id,
            ]);

            $achievement->load(['student', 'achievementType', 'giver', 'academicYear:id,name,code', 'semester:id,name']);
            return (new AchievementResource($achievement))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa atau jenis prestasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Achievement store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat prestasi.'], 500);
        }
    }

    public function show(Request $request, Achievement $achievement): AchievementResource|JsonResponse
    {
        if ($request->user()->institution_id !== $achievement->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $achievement->load(['student', 'achievementType', 'giver', 'academicYear:id,name,code', 'semester:id,name']);
        return new AchievementResource($achievement);
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement): AchievementResource|JsonResponse
    {
        try {
            if ($request->user()->institution_id !== $achievement->institution_id && !$request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $institutionId = $request->user()->institution_id;
            $type = AchievementType::where('id', $request->achievement_type_id)->where('institution_id', $institutionId)->where('is_active', true)->firstOrFail();

            $pointValue = $request->input('point_value', $type->point_value);

            $achievement->update([
                'achievement_type_id' => $type->id,
                'achievement_date' => $request->achievement_date,
                'point_value' => $pointValue,
                'notes' => $request->notes,
            ]);

            $achievement->load(['student', 'achievementType', 'giver', 'academicYear:id,name,code', 'semester:id,name']);
            return new AchievementResource($achievement);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jenis prestasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Achievement update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui prestasi.'], 500);
        }
    }

    public function destroy(Request $request, Achievement $achievement): JsonResponse
    {
        if ($request->user()->institution_id !== $achievement->institution_id && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $achievement->delete();
        return response()->json(['message' => 'Prestasi berhasil dihapus.']);
    }

    public function byStudent(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if ($user->isStudent()) {
                $profile = $user->studentProfile;
                if (!$profile || (int) $profile->id !== $studentId) {
                    return response()->json(['message' => 'Anda hanya dapat melihat data sendiri.'], 403);
                }
                $institutionId = $profile->institution_id;
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $items = Achievement::with(['achievementType', 'giver'])
                ->forInstitution($institutionId)
                ->forStudent($studentId)
                ->orderBy('achievement_date', 'desc')
                ->paginate(20);
            return AchievementResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Achievement byStudent failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil riwayat prestasi.'], 500);
        }
    }
}
