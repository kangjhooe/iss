<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreLibraryBookCopyRequest;
use App\Http\Requests\UpdateLibraryBookCopyRequest;
use App\Http\Resources\LibraryBookCopyResource;
use App\Models\LibraryBook;
use App\Models\LibraryBookCopy;
use App\Models\LibraryLoan;
use App\Services\LibraryInventoryNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LibraryBookCopyController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request)
    {
        try {
            $query = LibraryBookCopy::query()->with(['book', 'loans' => fn ($q) => $q->whereIn('status', ['Dipinjam', 'Terlambat'])->latest()->limit(1)]);

            if ($request->has('book_id')) {
                $query->where('book_id', $request->book_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('search')) {
                $q = $request->search;
                $query->where(function ($b) use ($q) {
                    $b->where('copy_code', 'like', "%{$q}%")
                        ->orWhereHas('book', fn ($b2) => $b2->where('title', 'like', "%{$q}%")->orWhere('author', 'like', "%{$q}%"));
                });
            }

            $institutionId = $this->resolveInstitutionId($request);
            if (!$request->user()->isAdminOrSuperAdmin()) {
                if ($institutionId === null) {
                    return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
                }
                $query->whereHas('book', fn ($q) => $q->where('institution_id', $institutionId));
            } elseif ($institutionId !== null) {
                $query->whereHas('book', fn ($q) => $q->where('institution_id', $institutionId));
            }

            $perPage = min($request->get('per_page', 15), 100);
            $copies = $query->orderBy('copy_code')->paginate($perPage);
            return LibraryBookCopyResource::collection($copies);
        } catch (\Exception $e) {
            Log::error('Library copies index', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data eksemplar.'], 500);
        }
    }

    public function store(StoreLibraryBookCopyRequest $request)
    {
        try {
            $data = $request->validated();
            $book = LibraryBook::with(['category', 'institution'])->findOrFail($data['book_id']);
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $book->institution_id)) {
                return $resp;
            }

            $inventory = app(LibraryInventoryNumberService::class);
            $quantity = (int) ($data['quantity'] ?? 1);
            if ($quantity < 1) {
                $quantity = 1;
            }

            $manualCode = trim((string) ($data['copy_code'] ?? ''));
            if ($manualCode !== '' && $quantity > 1) {
                return response()->json(['message' => 'Kode eksemplar manual hanya untuk 1 eksemplar. Kosongkan kode untuk generate otomatis.'], 422);
            }

            if ($manualCode !== '') {
                if ($inventory->codeExists((int) $book->institution_id, $manualCode)) {
                    return response()->json(['message' => 'Kode eksemplar sudah digunakan di institusi ini.'], 422);
                }
                $copy = LibraryBookCopy::create([
                    'book_id' => $book->id,
                    'copy_code' => $manualCode,
                    'status' => $data['status'] ?? 'Tersedia',
                    'condition' => $data['condition'] ?? 'Baik',
                    'notes' => $data['notes'] ?? null,
                ]);
                $copy->load('book');
                return response()->json([
                    'message' => 'Eksemplar berhasil ditambahkan.',
                    'data' => new LibraryBookCopyResource($copy),
                ], 201);
            }

            $created = $inventory->createCopies($book, $quantity, [
                'status' => $data['status'] ?? 'Tersedia',
                'condition' => $data['condition'] ?? 'Baik',
                'notes' => $data['notes'] ?? null,
            ]);

            if ($quantity === 1) {
                $copy = $created[0];
                $copy->load('book');
                return response()->json([
                    'message' => 'Eksemplar berhasil ditambahkan.',
                    'data' => new LibraryBookCopyResource($copy),
                ], 201);
            }

            return response()->json([
                'message' => "{$quantity} eksemplar berhasil ditambahkan.",
                'data' => LibraryBookCopyResource::collection(collect($created)->each->load('book')),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Library copy store', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal menambahkan eksemplar.'], 500);
        }
    }

    public function show(Request $request, LibraryBookCopy $copy)
    {
        try {
            $copy->load(['book', 'loans' => fn ($q) => $q->orderBy('loan_date', 'desc')->limit(10)]);
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) ($copy->book?->institution_id))) {
                return $resp;
            }
            return response()->json(['data' => new LibraryBookCopyResource($copy)]);
        } catch (\Exception $e) {
            Log::error('Library copy show', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data eksemplar.'], 500);
        }
    }

    public function update(UpdateLibraryBookCopyRequest $request, LibraryBookCopy $copy)
    {
        try {
            $copy->loadMissing('book');
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) ($copy->book?->institution_id))) {
                return $resp;
            }
            $data = $request->validated();
            if (isset($data['copy_code'])) {
                $inventory = app(LibraryInventoryNumberService::class);
                if ($inventory->codeExists((int) $copy->book->institution_id, $data['copy_code'], $copy->id)) {
                    return response()->json(['message' => 'Kode eksemplar sudah digunakan di institusi ini.'], 422);
                }
            }
            $copy->update($data);
            $copy->load('book');
            return response()->json([
                'message' => 'Eksemplar berhasil diperbarui.',
                'data' => new LibraryBookCopyResource($copy),
            ]);
        } catch (\Exception $e) {
            Log::error('Library copy update', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui eksemplar.'], 500);
        }
    }

    public function destroy(Request $request, LibraryBookCopy $copy)
    {
        try {
            $copy->loadMissing('book');
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) ($copy->book?->institution_id))) {
                return $resp;
            }
            $active = LibraryLoan::where('copy_id', $copy->id)->whereIn('status', ['Dipinjam', 'Terlambat'])->exists();
            if ($active) {
                return response()->json(['message' => 'Eksemplar sedang dipinjam. Tidak dapat dihapus.'], 422);
            }
            $copy->delete();
            return response()->json(['message' => 'Eksemplar berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Library copy destroy', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus eksemplar.'], 500);
        }
    }
}
