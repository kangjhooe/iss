<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\SubjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class SubjectController extends Controller
{
    public function __construct(protected SubjectService $subjectService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $filters = $request->only(['search', 'active_only', 'per_page']);
            $perPage = min($request->get('per_page', 15), 100);
            $result = $this->subjectService->listForInstitution($institutionId, $filters, $perPage);
            return SubjectResource::collection($result);
        } catch (\Exception $e) {
            Log::error('Subject index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data mata pelajaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $subject = $this->subjectService->create($institutionId, $request->validated());
            return (new SubjectResource($subject))->response()->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Subject store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan mata pelajaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, Subject $subject): SubjectResource|JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $subject->institution_id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return new SubjectResource($subject);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): SubjectResource|JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->institution_id !== $subject->institution_id && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $subject = $this->subjectService->update($subject, $request->validated());
            return new SubjectResource($subject);
        } catch (\Exception $e) {
            Log::error('Subject update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui mata pelajaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, Subject $subject): JsonResponse
    {
        $user = $request->user();
        if ($user->institution_id !== $subject->institution_id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($subject->lessonSchedules()->exists()) {
            return response()->json([
                'message' => 'Mata pelajaran tidak dapat dihapus karena sudah digunakan di jadwal.',
            ], 422);
        }
        $subject->delete();
        return response()->json(['message' => 'Mata pelajaran berhasil dihapus.']);
    }
}
