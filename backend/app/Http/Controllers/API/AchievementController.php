<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAchievementRequest;
use App\Http\Requests\UpdateAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\AchievementType;
use App\Models\Institution;
use App\Models\Student;
use App\Services\AchievementService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AchievementController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $academicYearId = $request->get('academic_year_id');
            $semesterId = $request->get('semester_id');
            $institution = Institution::find($institutionId);

            // Explicit empty = semua periode; jika param tidak dikirim, default ke aktif
            if ($request->has('academic_year_id') && $request->academic_year_id === '') {
                $academicYearId = null;
            } elseif (!$request->has('academic_year_id') && $institution?->active_academic_year_id) {
                $academicYearId = $institution->active_academic_year_id;
            }

            if ($request->has('semester_id') && $request->semester_id === '') {
                $semesterId = null;
            } elseif (!$request->has('semester_id') && $institution?->active_semester_id) {
                $semesterId = $institution->active_semester_id;
            }

            $query = Achievement::with(['student:id,name,nis,nisn', 'achievementType:id,name,point_value', 'giver:id,name', 'reviewer:id,name', 'academicYear:id,name,code', 'semester:id,name'])
                ->forInstitution($institutionId)
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
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
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('nis', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%");
                });
            }
            if ($request->filled('date_from')) {
                $query->whereDate('achievement_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('achievement_date', '<=', $request->date_to);
            }

            $pendingCount = Achievement::forInstitution($institutionId)
                ->where('status', Achievement::STATUS_PENDING)
                ->count();

            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);
            return AchievementResource::collection($items)->additional([
                'meta_extra' => ['pending_count' => $pendingCount],
            ]);
        } catch (\Exception $e) {
            Log::error('Achievement index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data prestasi.'], 500);
        }
    }

    public function store(StoreAchievementRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $achievement = $this->achievementService()->create(
                $institutionId,
                $request->validated(),
                $user->id,
                false
            );

            return (new AchievementResource($achievement))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa atau jenis prestasi tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Achievement store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat prestasi.'], 500);
        }
    }

    public function approve(Request $request, Achievement $achievement): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $achievement->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $data = $request->validate([
                'review_notes' => ['nullable', 'string', 'max:2000'],
                'point_value' => ['nullable', 'integer', 'min:0'],
            ]);
            $updated = $this->achievementService()->approve($achievement, $user, $data);

            return (new AchievementResource($updated))->response();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Achievement approve failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menyetujui usulan prestasi.'], 500);
        }
    }

    public function reject(Request $request, Achievement $achievement): JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $achievement->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            $data = $request->validate([
                'review_notes' => ['required', 'string', 'max:2000'],
            ]);
            $updated = $this->achievementService()->reject($achievement, $user, $data['review_notes']);

            return (new AchievementResource($updated))->response();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Achievement reject failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menolak usulan prestasi.'], 500);
        }
    }

    protected function achievementService(): AchievementService
    {
        return app(AchievementService::class);
    }

    public function show(Request $request, Achievement $achievement): AchievementResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $achievement->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $achievement->load(['student', 'achievementType', 'giver', 'academicYear:id,name,code', 'semester:id,name']);
        return new AchievementResource($achievement);
    }

    public function update(UpdateAchievementRequest $request, Achievement $achievement): AchievementResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $achievement->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($achievement->status !== Achievement::STATUS_PENDING) {
                $incomingType = (int) $request->achievement_type_id;
                $incomingDate = (string) $request->achievement_date;
                $incomingPoints = $request->input('point_value');
                $currentDate = optional($achievement->achievement_date)?->format('Y-m-d');
                $materialChanged = $incomingType !== (int) $achievement->achievement_type_id
                    || $incomingDate !== (string) $currentDate
                    || ($incomingPoints !== null && (float) $incomingPoints !== (float) $achievement->point_value);

                $reviewedByOther = $achievement->reviewed_by
                    && (int) $achievement->reviewed_by !== (int) $achievement->given_by;

                // Usulan yang sudah disetujui/ditolak reviewer lain tidak boleh diubah substansinya.
                if ($materialChanged && ($achievement->status === Achievement::STATUS_DITOLAK || $reviewedByOther)) {
                    return response()->json([
                        'message' => 'Prestasi yang sudah ditinjau tidak dapat diubah. Hapus lalu buat ulang, atau ajukan usulan baru.',
                    ], 422);
                }

                if ($materialChanged === false && $achievement->status !== Achievement::STATUS_PENDING) {
                    $achievement->update(['notes' => $request->notes]);
                    $achievement->load(['student', 'achievementType', 'giver', 'reviewer', 'academicYear:id,name,code', 'semester:id,name']);
                    return new AchievementResource($achievement);
                }
            }

            $institutionId = $this->resolveInstitutionId($request) ?: (int) $achievement->institution_id;
            $type = AchievementType::where('id', $request->achievement_type_id)->where('institution_id', $institutionId)->where('is_active', true)->firstOrFail();

            $pointValue = $request->input('point_value', $type->point_value);
            $institution = Institution::find($institutionId);
            $achievement->loadMissing('student');

            $payload = [
                'achievement_type_id' => $type->id,
                'achievement_date' => $request->achievement_date,
                'point_value' => $pointValue,
                'notes' => $request->notes,
            ];

            // Perbaiki data lama yang belum punya periode
            if (!$achievement->academic_year_id) {
                $payload['academic_year_id'] = $achievement->student?->academic_year_id
                    ?: $institution?->active_academic_year_id;
            }
            if (!$achievement->semester_id) {
                $payload['semester_id'] = $achievement->student?->semester_id
                    ?: $institution?->active_semester_id;
            }

            $achievement->update($payload);

            $achievement->load(['student', 'achievementType', 'giver', 'reviewer', 'academicYear:id,name,code', 'semester:id,name']);
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
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $achievement->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $achievement->delete();
        return response()->json(['message' => 'Prestasi berhasil dihapus.']);
    }

    public function byStudent(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
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
