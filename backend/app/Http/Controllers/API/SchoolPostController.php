<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolPostRequest;
use App\Http\Requests\UpdateSchoolPostRequest;
use App\Http\Resources\SchoolPostResource;
use App\Models\SchoolPost;
use App\Services\SchoolPostService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class SchoolPostController extends Controller
{
    public function __construct(
        protected SchoolPostService $service
    ) {}

    protected function getInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->filled('institution_id') ? $request->get('institution_id') : null
        );
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['search', 'type', 'is_published']);
            $perPage = min((int) $request->get('per_page', 15), 100);
            $posts = $this->service->list($filters, $institutionId, $perPage);

            return SchoolPostResource::collection($posts);
        } catch (\Exception $e) {
            Log::error('SchoolPost index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil konten sekolah.'], 500);
        }
    }

    public function store(StoreSchoolPostRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            unset($data['cover'], $data['gallery']);
            $gallery = $request->file('gallery', []) ?: [];
            if (!is_array($gallery)) {
                $gallery = [$gallery];
            }

            $post = $this->service->create(
                $data,
                $institutionId,
                $request->user()->id,
                $request->file('cover'),
                $gallery
            );

            return response()->json([
                'message' => 'Konten berhasil disimpan.',
                'data' => new SchoolPostResource($post),
            ], 201);
        } catch (\Exception $e) {
            Log::error('SchoolPost store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menyimpan konten. ' . $e->getMessage()], 500);
        }
    }

    public function show(Request $request, SchoolPost $school_post): SchoolPostResource|JsonResponse
    {
        $institutionId = $this->getInstitutionId($request);
        if ($institutionId && (int) $school_post->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $school_post->load(['images', 'creator:id,name']);

        return new SchoolPostResource($school_post);
    }

    public function update(UpdateSchoolPostRequest $request, SchoolPost $school_post): JsonResponse
    {
        try {
            $institutionId = $this->getInstitutionId($request);
            if ($institutionId && (int) $school_post->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            unset($data['cover'], $data['gallery']);
            $gallery = $request->file('gallery', []) ?: [];
            if (!is_array($gallery)) {
                $gallery = [$gallery];
            }

            $post = $this->service->update($school_post, $data, $request->file('cover'), $gallery);

            return response()->json([
                'message' => 'Konten berhasil diperbarui.',
                'data' => new SchoolPostResource($post),
            ]);
        } catch (\Exception $e) {
            Log::error('SchoolPost update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui konten.'], 500);
        }
    }

    public function destroy(Request $request, SchoolPost $school_post): JsonResponse
    {
        $institutionId = $this->getInstitutionId($request);
        if ($institutionId && (int) $school_post->institution_id !== $institutionId && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->service->delete($school_post);

        return response()->json(['message' => 'Konten berhasil dihapus.']);
    }

    /**
     * Publik: berita & galeri by NPSN.
     */
    public function publicByNpsn(Request $request): JsonResponse
    {
        $npsn = preg_replace('/\D/', '', (string) $request->get('npsn', ''));
        if (strlen($npsn) !== 8) {
            return response()->json(['message' => 'npsn harus 8 digit.'], 422);
        }

        $institution = \App\Models\Institution::query()
            ->where('npsn', $npsn)
            ->where('is_active', true)
            ->first();
        if (!$institution) {
            return response()->json(['message' => 'Sekolah tidak ditemukan.'], 404);
        }

        $type = $request->get('type');
        $query = SchoolPost::forInstitution((int) $institution->id)
            ->published()
            ->with('images')
            ->orderByDesc('sort')
            ->orderByDesc('published_at');
        if (in_array($type, ['news', 'gallery'], true)) {
            $query->where('type', $type);
        }

        $posts = $query->paginate($perPage);

        return SchoolPostResource::collection($posts)->response();
    }
}
