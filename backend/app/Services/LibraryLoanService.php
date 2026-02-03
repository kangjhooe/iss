<?php

namespace App\Services;

use App\Models\LibraryBookCopy;
use App\Models\LibraryLoan;
use App\Models\LibraryFinePayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LibraryLoanService
{
    /** Default loan days */
    public const DEFAULT_LOAN_DAYS = 7;

    /** Fine per day (IDR) for late return */
    public const FINE_PER_DAY = 2000;

    public function listLoans(array $filters, ?int $institutionId, int $perPage = 15): LengthAwarePaginator
    {
        $query = LibraryLoan::query()
            ->forInstitution($institutionId)
            ->with(['copy.book', 'finePayments', 'creator']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['borrower_type'])) {
            $query->where('borrower_type', $filters['borrower_type']);
        }
        if (!empty($filters['copy_id'])) {
            $query->where('copy_id', $filters['copy_id']);
        }
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function ($b) use ($q) {
                $b->where('borrower_name', 'like', "%{$q}%")
                    ->orWhere('borrower_identifier', 'like', "%{$q}%");
            });
        }

        return $query->orderBy('loan_date', 'desc')->paginate($perPage);
    }

    public function createLoan(array $data, int $institutionId, ?int $userId): LibraryLoan
    {
        return DB::transaction(function () use ($data, $institutionId, $userId) {
            $copy = LibraryBookCopy::findOrFail($data['copy_id']);
            if ($copy->status !== 'Tersedia') {
                throw new \RuntimeException('Eksemplar tidak tersedia untuk dipinjam.');
            }
            $activeLoan = LibraryLoan::where('copy_id', $copy->id)
                ->whereIn('status', ['Dipinjam', 'Terlambat'])
                ->exists();
            if ($activeLoan) {
                throw new \RuntimeException('Eksemplar sedang dipinjam.');
            }

            $data['institution_id'] = $institutionId;
            $data['created_by'] = $userId;
            $data['status'] = 'Dipinjam';

            $loan = LibraryLoan::create($data);
            $copy->update(['status' => 'Dipinjam']);

            return $loan->load(['copy.book', 'creator']);
        });
    }

    public function returnLoan(LibraryLoan $loan, ?float $fineAmount = null, ?string $notes = null): LibraryLoan
    {
        return DB::transaction(function () use ($loan, $fineAmount, $notes) {
            if (!in_array($loan->status, ['Dipinjam', 'Terlambat'], true)) {
                throw new \RuntimeException('Peminjaman ini sudah dikembalikan atau tidak valid.');
            }

            $copy = $loan->copy;
            $returnedAt = now();
            $loan->returned_at = $returnedAt;
            $loan->status = 'Dikembalikan';
            if ($fineAmount !== null) {
                $loan->fine_amount = $fineAmount;
            }
            if ($notes !== null) {
                $loan->notes = ($loan->notes ? $loan->notes . "\n" : '') . $notes;
            }
            $loan->save();

            $copy->update(['status' => 'Tersedia']);

            return $loan->load(['copy.book', 'finePayments', 'creator']);
        });
    }

    public function calculateLateFine(LibraryLoan $loan): float
    {
        if ($loan->returned_at) {
            $end = Carbon::parse($loan->returned_at);
        } else {
            $end = now();
        }
        $due = Carbon::parse($loan->due_date)->startOfDay();
        if ($end->lte($due)) {
            return 0;
        }
        $days = $due->diffInDays($end, false);
        return max(0, $days * self::FINE_PER_DAY);
    }

    public function payFine(LibraryLoan $loan, float $amount, $paidAt, ?string $paymentMethod, ?string $notes, ?int $userId): LibraryFinePayment
    {
        $payment = LibraryFinePayment::create([
            'loan_id' => $loan->id,
            'amount' => $amount,
            'paid_at' => $paidAt,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
            'created_by' => $userId,
        ]);
        return $payment;
    }

    /**
     * Perpanjang peminjaman: perpanjang due_date (default +7 hari).
     */
    public function renewLoan(LibraryLoan $loan, int $extraDays = self::DEFAULT_LOAN_DAYS): LibraryLoan
    {
        if (!in_array($loan->status, ['Dipinjam', 'Terlambat'], true)) {
            throw new \RuntimeException('Hanya peminjaman yang masih aktif yang dapat diperpanjang.');
        }
        if ($loan->returned_at) {
            throw new \RuntimeException('Peminjaman ini sudah dikembalikan.');
        }
        $newDue = Carbon::parse($loan->due_date)->addDays($extraDays);
        $loan->due_date = $newDue;
        $loan->status = $newDue->isPast() ? 'Terlambat' : 'Dipinjam';
        $loan->save();
        return $loan->fresh(['copy.book', 'finePayments', 'creator']);
    }

    /**
     * Mark overdue loans as Terlambat (call from scheduler or on demand).
     */
    public function updateOverdueStatus(?int $institutionId = null): int
    {
        $query = LibraryLoan::whereIn('status', ['Dipinjam'])
            ->where('due_date', '<', now()->toDateString())
            ->whereNull('returned_at');
        if ($institutionId !== null) {
            $query->where('institution_id', $institutionId);
        }
        return $query->update(['status' => 'Terlambat']);
    }
}
