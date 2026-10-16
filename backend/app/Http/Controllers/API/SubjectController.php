<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\SubjectService;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class SubjectController extends Controller
{
    public function __construct(protected SubjectService $subjectService)
    {
    }

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    private function canAccessSubject($user, Subject $subject): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return InstitutionContext::canAccessInstitution($user, (int) $subject->institution_id);
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
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
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $subject = $this->subjectService->create($institutionId, $request->validated());
            $subject->load(['catalog:id,code,name,jenjang']);

            return (new SubjectResource($subject))->response()->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
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
        if (!$this->canAccessSubject($user, $subject)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return new SubjectResource($subject);
    }

    public function update(UpdateSubjectRequest $request, Subject $subject): SubjectResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$this->canAccessSubject($user, $subject)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $subject = $this->subjectService->update($subject, $request->validated());
            return new SubjectResource($subject);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
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
        if (!$this->canAccessSubject($user, $subject)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $usage = $subject->lessonSchedules()
            ->with(['schoolClass:id,name'])
            ->get(['id', 'class_id', 'semester_id', 'day_of_week', 'period']);

        if ($usage->isNotEmpty()) {
            $byClass = $usage->groupBy('class_id')->map(function ($rows) {
                $name = $rows->first()?->schoolClass?->name ?: ('Kelas #'.$rows->first()->class_id);

                return $name.' ('.$rows->count().' slot)';
            })->values()->take(5)->all();

            $more = $usage->groupBy('class_id')->count() > 5
                ? ' …'
                : '';

            return response()->json([
                'message' => 'Mata pelajaran tidak dapat dihapus karena masih dipakai di '.$usage->count()
                    .' slot jadwal: '.implode(', ', $byClass).$more
                    .'. Kosongkan jadwal terkait dulu.',
            ], 422);
        }

        $subject->delete();
        return response()->json(['message' => 'Mata pelajaran berhasil dihapus.']);
    }
}
