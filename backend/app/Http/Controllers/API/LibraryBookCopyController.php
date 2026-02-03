<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLibraryBookCopyRequest;
use App\Http\Requests\UpdateLibraryBookCopyRequest;
use App\Http\Resources\LibraryBookCopyResource;
use App\Models\LibraryBookCopy;
use App\Models\LibraryLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LibraryBookCopyController extends Controller
{
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

            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }
            if ($institutionId !== null) {
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
            $book = \App\Models\LibraryBook::findOrFail($data['book_id']);
            if (!$request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id !== $book->institution_id) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }
            $exists = LibraryBookCopy::where('book_id', $book->id)->where('copy_code', $data['copy_code'])->exists();
            if ($exists) {
                return response()->json(['message' => 'Kode eksemplar sudah ada untuk buku ini.'], 422);
            }
            $copy = LibraryBookCopy::create([
                'book_id' => $data['book_id'],
                'copy_code' => $data['copy_code'],
                'status' => $data['status'] ?? 'Tersedia',
                'condition' => $data['condition'] ?? 'Baik',
                'notes' => $data['notes'] ?? null,
            ]);
            $copy->load('book');
            return response()->json([
                'message' => 'Eksemplar berhasil ditambahkan.',
                'data' => new LibraryBookCopyResource($copy),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Library copy store', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage() ?: 'Gagal menambahkan eksemplar.'], 500);
        }
    }

    public function show(LibraryBookCopy $copy)
    {
        try {
            $copy->load(['book', 'loans' => fn ($q) => $q->orderBy('loan_date', 'desc')->limit(10)]);
            return response()->json(['data' => new LibraryBookCopyResource($copy)]);
        } catch (\Exception $e) {
            Log::error('Library copy show', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data eksemplar.'], 500);
        }
    }

    public function update(UpdateLibraryBookCopyRequest $request, LibraryBookCopy $copy)
    {
        try {
            $data = $request->validated();
            if (isset($data['copy_code'])) {
                $exists = LibraryBookCopy::where('book_id', $copy->book_id)->where('copy_code', $data['copy_code'])->where('id', '!=', $copy->id)->exists();
                if ($exists) {
                    return response()->json(['message' => 'Kode eksemplar sudah ada untuk buku ini.'], 422);
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

    public function destroy(LibraryBookCopy $copy)
    {
        try {
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
