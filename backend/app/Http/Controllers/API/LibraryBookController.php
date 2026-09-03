<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreLibraryBookRequest;
use App\Http\Requests\UpdateLibraryBookRequest;
use App\Http\Resources\LibraryBookResource;
use App\Support\StructuralPositionResolver;
use App\Models\Institution;
use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use App\Services\LibraryBookImportService;
use App\Services\LibraryBookService;
use App\Services\LibraryEbookViewService;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LibraryBookController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        private LibraryBookService $service,
        private LibraryBookImportService $importService,
        private LibraryEbookViewService $ebookViewService
    ) {}

    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            $filters = $request->only(['search', 'category_id', 'has_ebook', 'sort_by', 'sort_dir']);
            $perPage = min((int) $request->get('per_page', 25), 25);
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
            $ebook = $request->hasFile('ebook') ? $request->file('ebook') : null;
            $book = $this->service->createBook($data, $institutionId, $request->user()->id, $cover, $ebook);
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

    public function show(Request $request, LibraryBook $book)
    {
        try {
            if ($resp = $this->denyUnlessBookInInstitution($request, $book)) {
                return $resp;
            }
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
            if ($resp = $this->denyUnlessBookInInstitution($request, $book)) {
                return $resp;
            }
            $data = $request->validated();
            $cover = $request->hasFile('cover') ? $request->file('cover') : null;
            $ebook = $request->hasFile('ebook') ? $request->file('ebook') : null;
            $updated = $this->service->updateBook($book, $data, $request->user()->id, $cover, $ebook);
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

    public function destroy(Request $request, LibraryBook $book)
    {
        try {
            if ($resp = $this->denyUnlessBookInInstitution($request, $book)) {
                return $resp;
            }
            if ($book->copies()->whereIn('status', ['Dipinjam'])->exists()) {
                return response()->json(['message' => 'Ada eksemplar yang masih dipinjam. Tidak dapat menghapus buku.'], 422);
            }
            $this->service->deleteEbookFile($book);
            $book->delete();
            return response()->json(['message' => 'Buku berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Library book destroy', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus buku.'], 500);
        }
    }

    /**
     * Pastikan buku milik institusi yang boleh diakses user (cegah akses lintas tenant via ID).
     */
    private function denyUnlessBookInInstitution(Request $request, LibraryBook $book): ?\Illuminate\Http\JsonResponse
    {
        return $this->denyUnlessCanAccessInstitution($request, (int) $book->institution_id);
    }

    /**
     * Import katalog dari array baris (Excel diparse di frontend, sama seperti import siswa).
     * Tidak memvalidasi nested field sebagai string ketat — Excel sering kirim ISBN/tahun sebagai angka.
     */
    public function import(Request $request)
    {
        try {
            $books = $request->input('books', []);

            if (!is_array($books) || $books === []) {
                return response()->json([
                    'message' => 'Data buku tidak valid. Pastikan frontend mengirim array books (bukan unggah file mentah).',
                ], 400);
            }

            if (count($books) > 2000) {
                return response()->json([
                    'message' => 'Maksimal 2000 baris per permintaan. Pecah file atau gunakan batch otomatis di UI.',
                ], 422);
            }

            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }

            $results = $this->importService->importFromRows(
                $books,
                (int) $institutionId,
                $request->user()->id
            );

            return response()->json([
                'message' => 'Import selesai',
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Library books import', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => $e->getMessage() ?: 'Gagal mengimpor katalog buku.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Unduh daftar kode kategori aktif (untuk bantu isi template di frontend).
     */
    public function importTemplate(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }

            $categories = LibraryBookCategory::query()
                ->forInstitution($institutionId)
                ->active()
                ->orderBy('code')
                ->get(['code', 'name']);

            return response()->json([
                'data' => [
                    'headers' => LibraryBookImportService::HEADERS,
                    'categories' => $categories,
                    'sample_kode_kategori' => $categories->first()?->code ?? 'FKS',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Library books import template meta', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data template.'], 500);
        }
    }

    /**
     * Export katalog ke CSV (mengikuti filter pencarian/kategori).
     */
    public function exportCsv(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }
            $filters = $request->only(['search', 'category_id']);
            $csv = $this->importService->exportCsv($institutionId, $filters);
            $filename = 'katalog_buku_' . date('Y-m-d_His') . '.csv';
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('Library books export CSV', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengekspor katalog.'], 500);
        }
    }

    /**
     * Cetak katalog buku sebagai PDF.
     */
    public function exportPdf(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }

            $filters = $request->only(['search', 'category_id']);
            $query = LibraryBook::query()
                ->forInstitution($institutionId)
                ->with(['category'])
                ->withCount('copies');

            if (!empty($filters['search'])) {
                $q = $filters['search'];
                $query->where(function ($b) use ($q) {
                    $b->where('title', 'like', "%{$q}%")
                        ->orWhere('author', 'like', "%{$q}%")
                        ->orWhere('isbn', 'like', "%{$q}%");
                });
            }
            if (!empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            $books = $query->orderBy('title')->limit(2000)->get();
            $institution = Institution::find($institutionId);

            $filterParts = [];
            if (!empty($filters['search'])) {
                $filterParts[] = 'Pencarian: ' . $filters['search'];
            }
            if (!empty($filters['category_id'])) {
                $cat = LibraryBookCategory::find($filters['category_id']);
                if ($cat) {
                    $filterParts[] = 'Kategori: ' . $cat->code . ' - ' . $cat->name;
                }
            }

            $pdf = DomPDF::loadView('library.katalog_buku', [
                'institution' => $institution,
                'books' => $books,
                'filter_label' => $filterParts ? implode(' · ', $filterParts) : null,
                'printed_at' => now()->locale('id')->isoFormat('D MMMM YYYY HH:mm'),
                'kepala_perpustakaan' => StructuralPositionResolver::holderObject('ketua_perpus', (int) $institutionId),
                'as_of_date' => now(),
            ]);

            $filename = 'Katalog_Buku_' . date('Y-m-d_His') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Library books export PDF', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencetak katalog buku.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Katalog ebook (buku yang punya PDF) — bisa diakses siswa & staf institusi yang sama.
     */
    public function ebooks(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }
            $filters = $request->only(['search', 'category_id']);
            $perPage = min((int) $request->get('per_page', 15), 100);
            $items = $this->service->listEbooks($filters, $institutionId, $perPage);
            return LibraryBookResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Library ebooks index', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data ebook.', 'error' => config('app.debug') ? $e->getMessage() : null], 500);
        }
    }

    /**
     * Stream PDF ebook (inline) — auth + scope institusi + catat view.
     */
    public function streamEbook(Request $request, LibraryBook $book): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $book->institution_id)) {
                return $resp;
            }
            if (!$book->hasEbook() || !Storage::disk('local')->exists($book->ebook_path)) {
                return response()->json(['message' => 'Ebook tidak tersedia.'], 404);
            }

            $user = $request->user();
            $source = $user && $user->isStudent() ? 'student' : 'staff';
            try {
                $this->ebookViewService->recordView($book, $request, $source);
            } catch (\Throwable $e) {
                Log::warning('Library ebook view tracking failed', [
                    'error' => $e->getMessage(),
                    'book_id' => $book->id,
                ]);
            }

            $filename = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $book->title) . '.pdf';

            return Storage::disk('local')->response(
                $book->ebook_path,
                $filename,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="' . $filename . '"',
                    'Cache-Control' => 'private, max-age=0, must-revalidate',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Library ebook stream', ['error' => $e->getMessage(), 'book_id' => $book->id]);
            return response()->json(['message' => 'Gagal membuka ebook.'], 500);
        }
    }
}
