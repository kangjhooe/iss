<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LibraryBookCategoryResource;
use App\Http\Resources\LibraryBookResource;
use App\Models\Institution;
use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use App\Services\LibraryBookService;
use App\Services\LibraryEbookAccessTokenService;
use App\Services\LibraryEbookViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicLibraryController extends Controller
{
    public function __construct(
        private LibraryBookService $bookService,
        private LibraryEbookViewService $viewService,
        private LibraryEbookAccessTokenService $tokenService
    ) {}

    /**
     * Katalog ebook publik by NPSN.
     */
    public function ebooks(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institution = $this->resolveInstitutionByNpsn($request);
            if (!$institution) {
                return response()->json(['message' => 'Sekolah/madrasah tidak ditemukan.'], 404);
            }

            $filters = $request->only(['search', 'category_id']);
            $perPage = min((int) $request->get('per_page', 12), 50);
            $items = $this->bookService->listPublicEbooks($filters, (int) $institution->id, $perPage);

            return LibraryBookResource::collection($items)->additional([
                'institution' => [
                    'id' => $institution->id,
                    'name' => $institution->name,
                    'npsn' => $institution->npsn,
                    'logo_url' => $institution->logo ? asset('storage/' . $institution->logo) : null,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Public library ebooks', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil katalog ebook.'], 500);
        }
    }

    /**
     * Kategori untuk filter katalog ebook publik.
     */
    public function categories(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institution = $this->resolveInstitutionByNpsn($request);
            if (!$institution) {
                return response()->json(['message' => 'Sekolah/madrasah tidak ditemukan.'], 404);
            }

            $categories = LibraryBookCategory::query()
                ->where('institution_id', $institution->id)
                ->where('is_active', true)
                ->whereHas('books', function ($q) {
                    $q->whereNotNull('ebook_path')->where('is_public_ebook', true);
                })
                ->orderBy('code')
                ->get();

            return LibraryBookCategoryResource::collection($categories);
        } catch (\Exception $e) {
            Log::error('Public library categories', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil kategori.'], 500);
        }
    }

    /**
     * Terbitkan sesi viewer (signed stream URL + watermark). Catat view di sini (sekali per buka).
     */
    public function issueViewer(Request $request, LibraryBook $book): JsonResponse
    {
        try {
            $institution = $this->resolveInstitutionByNpsn($request);
            if (!$institution || (int) $book->institution_id !== (int) $institution->id) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }
            if (!$book->isPublicEbook() || !Storage::disk('local')->exists($book->ebook_path)) {
                return response()->json(['message' => 'Ebook tidak tersedia.'], 404);
            }

            try {
                $this->viewService->recordView($book, $request, 'public');
            } catch (\Throwable $e) {
                Log::warning('Public library ebook view tracking failed', [
                    'error' => $e->getMessage(),
                    'book_id' => $book->id,
                ]);
            }

            $issued = $this->tokenService->issue($book, $institution);
            $streamPath = '/api/v1/public/library/books/' . $book->id . '/ebook?' . http_build_query([
                'npsn' => $institution->npsn,
                'token' => $issued['token'],
                'expires' => $issued['expires'],
            ]);

            return response()->json([
                'data' => [
                    'book_id' => $book->id,
                    'title' => $book->title,
                    'stream_path' => $streamPath,
                    'expires_at' => date('c', $issued['expires']),
                    'watermark' => $this->tokenService->watermarkText($institution),
                    'institution' => [
                        'name' => $institution->name,
                        'npsn' => $institution->npsn,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Public library issue viewer', ['error' => $e->getMessage(), 'book_id' => $book->id]);
            return response()->json(['message' => 'Gagal membuka ebook.'], 500);
        }
    }

    /**
     * Stream PDF untuk PDF.js (Range request). Wajib token signed yang valid.
     */
    public function streamEbook(Request $request, LibraryBook $book): BinaryFileResponse|JsonResponse
    {
        try {
            $institution = $this->resolveInstitutionByNpsn($request);
            if (!$institution || (int) $book->institution_id !== (int) $institution->id) {
                return response()->json(['message' => 'Akses ditolak.'], 403);
            }
            if (!$book->isPublicEbook() || !Storage::disk('local')->exists($book->ebook_path)) {
                return response()->json(['message' => 'Ebook tidak tersedia.'], 404);
            }

            $valid = $this->tokenService->validate(
                $book,
                $institution,
                $request->query('token'),
                $request->query('expires')
            );
            if (!$valid) {
                return response()->json(['message' => 'Sesi baca kedaluwarsa. Buka ulang ebook.'], 403);
            }

            $absolutePath = Storage::disk('local')->path($book->ebook_path);

            $response = response()->file($absolutePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
                'Accept-Ranges' => 'bytes',
            ]);

            // PDF.js butuh Range; BinaryFileResponse Laravel sudah mendukungnya.
            return $response;
        } catch (\Exception $e) {
            Log::error('Public library ebook stream', ['error' => $e->getMessage(), 'book_id' => $book->id]);
            return response()->json(['message' => 'Gagal membuka ebook.'], 500);
        }
    }

    private function resolveInstitutionByNpsn(Request $request): ?Institution
    {
        $npsn = preg_replace('/\D/', '', (string) $request->get('npsn', ''));
        if (strlen($npsn) !== 8) {
            return null;
        }

        return Institution::where('npsn', $npsn)->where('is_active', true)->first();
    }
}
