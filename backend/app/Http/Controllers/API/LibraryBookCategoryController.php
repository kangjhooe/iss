<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibraryBookCategoryRequest;
use App\Http\Requests\UpdateLibraryBookCategoryRequest;
use App\Http\Resources\LibraryBookCategoryResource;
use App\Models\LibraryBookCategory;
use App\Services\LibraryBookService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LibraryBookCategoryController extends Controller
{
    public function __construct(
        private LibraryBookService $service
    ) {}

    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            $filters = $request->only(['search', 'is_active']);
            $perPage = min($request->get('per_page', 15), 100);
            $items = $this->service->listCategories($filters, $institutionId, $perPage);
            return LibraryBookCategoryResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Library categories index', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data kategori.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function store(StoreLibraryBookCategoryRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }
            $data = $request->validated();
            if (LibraryBookCategory::where('institution_id', $institutionId)->where('code', $data['code'])->exists()) {
                return response()->json(['message' => 'Kode kategori sudah digunakan di institusi ini.'], 422);
            }
            $category = $this->service->createCategory($data, $institutionId);
            return response()->json([
                'message' => 'Kategori buku berhasil ditambahkan.',
                'data' => new LibraryBookCategoryResource($category),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Library category store', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal menambahkan kategori.'], 500);
        }
    }

    public function show(LibraryBookCategory $category)
    {
        try {
            $category->loadCount('books');
            return response()->json(['data' => new LibraryBookCategoryResource($category)]);
        } catch (\Exception $e) {
            Log::error('Library category show', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data kategori.'], 500);
        }
    }

    public function update(UpdateLibraryBookCategoryRequest $request, LibraryBookCategory $category)
    {
        try {
            $updated = $this->service->updateCategory($category, $request->validated());
            return response()->json([
                'message' => 'Kategori buku berhasil diperbarui.',
                'data' => new LibraryBookCategoryResource($updated),
            ]);
        } catch (\Exception $e) {
            Log::error('Library category update', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal memperbarui kategori.'], 500);
        }
    }

    public function destroy(LibraryBookCategory $category)
    {
        try {
            if ($category->books()->count() > 0) {
                return response()->json(['message' => 'Kategori masih memiliki buku. Hapus atau pindahkan buku terlebih dahulu.'], 422);
            }
            $category->delete();
            return response()->json(['message' => 'Kategori buku berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Library category destroy', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus kategori.'], 500);
        }
    }

    private function resolveInstitutionId(Request $request): ?int
    {
        if (!$request->user()->isAdminOrSuperAdmin()) {
            return $request->user()->institution_id;
        }
        return $request->input('institution_id');
    }
}
