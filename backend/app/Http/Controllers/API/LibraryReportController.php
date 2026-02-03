<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\LibraryBook;
use App\Models\LibraryBookCategory;
use App\Models\LibraryBookCopy;
use App\Models\LibraryLoan;
use App\Models\LibraryFinePayment;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LibraryReportController extends Controller
{
    public function statistics(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }
            if (!$institutionId) {
                return response()->json(['data' => [
                    'total_books' => 0,
                    'total_copies' => 0,
                    'total_categories' => 0,
                    'available_copies' => 0,
                    'borrowed_copies' => 0,
                    'overdue_count' => 0,
                    'total_loans' => 0,
                    'total_fines_collected' => 0,
                ]]);
            }

            $booksQuery = LibraryBook::where('institution_id', $institutionId);
            $totalBooks = $booksQuery->count();
            $totalCategories = LibraryBookCategory::where('institution_id', $institutionId)->count();
            $copyIds = LibraryBookCopy::whereHas('book', fn ($q) => $q->where('institution_id', $institutionId))->pluck('id');
            $totalCopies = $copyIds->count();
            $availableCopies = LibraryBookCopy::whereIn('id', $copyIds)->where('status', 'Tersedia')->count();
            $borrowedCopies = LibraryBookCopy::whereIn('id', $copyIds)->where('status', 'Dipinjam')->count();
            $overdueCount = LibraryLoan::where('institution_id', $institutionId)->where('status', 'Terlambat')->whereNull('returned_at')->count();
            $totalLoans = LibraryLoan::where('institution_id', $institutionId)->count();
            $totalFinesCollected = LibraryFinePayment::whereHas('loan', fn ($q) => $q->where('institution_id', $institutionId))->sum('amount');

            return response()->json(['data' => [
                'total_books' => $totalBooks,
                'total_copies' => $totalCopies,
                'total_categories' => $totalCategories,
                'available_copies' => $availableCopies,
                'borrowed_copies' => $borrowedCopies,
                'overdue_count' => $overdueCount,
                'total_loans' => $totalLoans,
                'total_fines_collected' => (float) $totalFinesCollected,
            ]]);
        } catch (\Exception $e) {
            Log::error('Library report statistics', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil statistik.'], 500);
        }
    }

    public function topBooks(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }
            if (!$institutionId) {
                return response()->json(['data' => []]);
            }
            $limit = min($request->get('limit', 10), 50);
            $top = LibraryLoan::where('library_loans.institution_id', $institutionId)
                ->join('library_book_copies', 'library_loans.copy_id', '=', 'library_book_copies.id')
                ->join('library_books', 'library_book_copies.book_id', '=', 'library_books.id')
                ->where('library_books.institution_id', $institutionId)
                ->select('library_books.id', 'library_books.title', 'library_books.author', DB::raw('count(*) as loan_count'))
                ->groupBy('library_books.id', 'library_books.title', 'library_books.author')
                ->orderByDesc('loan_count')
                ->limit($limit)
                ->get();
            return response()->json(['data' => $top]);
        } catch (\Exception $e) {
            Log::error('Library report top books', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data.'], 500);
        }
    }

    public function loansByMonth(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }
            if (!$institutionId) {
                return response()->json(['data' => []]);
            }
            $year = $request->get('year', date('Y'));
            $rows = LibraryLoan::where('institution_id', $institutionId)
                ->whereYear('loan_date', $year)
                ->select(DB::raw('MONTH(loan_date) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            return response()->json(['data' => $rows]);
        } catch (\Exception $e) {
            Log::error('Library report loans by month', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data.'], 500);
        }
    }

    /**
     * Export laporan peminjaman buku sebagai PDF (dengan statistik).
     */
    public function exportLoansPdf(Request $request)
    {
        try {
            $institutionId = null;
            if (!$request->user()->isAdminOrSuperAdmin()) {
                $institutionId = $request->user()->institution_id;
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 400);
            }

            $dateFrom = $request->get('date_from');
            $dateTo = $request->get('date_to');
            $status = $request->get('status');

            $query = LibraryLoan::query()
                ->where('institution_id', $institutionId)
                ->with(['copy.book']);

            if ($dateFrom) {
                $query->whereDate('loan_date', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('loan_date', '<=', $dateTo);
            }
            if ($status && in_array($status, ['Dipinjam', 'Terlambat', 'Dikembalikan'], true)) {
                $query->where('status', $status);
            }

            $loans = $query->orderBy('loan_date', 'desc')->orderBy('id', 'desc')->limit(2000)->get();

            $stats = [
                'total_loans' => $loans->count(),
                'still_borrowed' => $loans->whereIn('status', ['Dipinjam', 'Terlambat'])->count(),
                'overdue_count' => $loans->where('status', 'Terlambat')->count(),
                'returned_count' => $loans->where('status', 'Dikembalikan')->count(),
                'total_fines' => (float) $loans->sum('fine_amount'),
            ];

            $institution = Institution::find($institutionId);
            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');

            $pdf = DomPDF::loadView('library.laporan_peminjaman', [
                'institution' => $institution,
                'loans' => $loans,
                'stats' => $stats,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'printed_at' => $printedAt,
            ]);

            $filename = 'Laporan_Peminjaman_Perpustakaan_' . date('Y-m-d_His') . '.pdf';
            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('Library export loans PDF failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencetak laporan peminjaman.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
