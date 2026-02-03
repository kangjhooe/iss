<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibraryBookRequest;
use App\Http\Requests\UpdateLibraryBookRequest;
use App\Http\Resources\LibraryBookResource;
use App\Models\LibraryBook;
use App\Services\LibraryBookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryBookController extends Controller
{
    public function __construct(
        private LibraryBookService $service
    ) {}

    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            $filters = $request->only(['search', 'category_id']);
            $perPage = min($request->get('per_page', 15), 100);
            $items = $this->service->listBooks($filters, $institutionId, $perPage);
            return LibraryBookResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Library books index', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data buku.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    public function store(StoreLibraryBookRequest $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }
            $data = $request->validated();
            $cover = $request->hasFile('cover') ? $request->file('cover') : null;
            $book = $this->service->createBook($data, $institutionId, $request->user()->id, $cover);
            $book->load(['category', 'creator']);
            return response()->json([
                'message' => 'Buku berhasil ditambahkan.',
                'data' => new LibraryBookResource($book),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Library book store', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal menambahkan buku.'], 500);
        }
    }

    public function show(LibraryBook $book)
    {
        try {
            $book->load(['category', 'copies', 'creator', 'updater']);
            return response()->json(['data' => new LibraryBookResource($book)]);
        } catch (\Exception $e) {
            Log::error('Library book show', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data buku.'], 500);
        }
    }

    public function update(UpdateLibraryBookRequest $request, LibraryBook $book)
    {
        try {
            $data = $request->validated();
            $cover = $request->hasFile('cover') ? $request->file('cover') : null;
            $updated = $this->service->updateBook($book, $data, $request->user()->id, $cover);
            $updated->load(['category', 'creator', 'updater']);
            return response()->json([
                'message' => 'Buku berhasil diperbarui.',
                'data' => new LibraryBookResource($updated),
            ]);
        } catch (\Exception $e) {
            Log::error('Library book update', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal memperbarui buku.'], 500);
        }
    }

    public function destroy(LibraryBook $book)
    {
        try {
            if ($book->copies()->whereIn('status', ['Dipinjam'])->exists()) {
                return response()->json(['message' => 'Ada eksemplar yang masih dipinjam. Tidak dapat menghapus buku.'], 422);
            }
            $book->delete();
            return response()->json(['message' => 'Buku berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Library book destroy', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus buku.'], 500);
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
