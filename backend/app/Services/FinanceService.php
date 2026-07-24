<?php

namespace App\Services;

use App\Models\FinanceFeeType;
use App\Models\FinanceInvoice;
use App\Models\FinancePayment;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinanceService
{
    /**
     * Generate invoices for selected students (or a whole class / all active students).
     *
     * @param  array<string, mixed>  $payload
     * @return array{created:int, skipped:int, batch_key:string, invoices:Collection<int, FinanceInvoice>}
     */
    public function generateInvoices(int $institutionId, int $userId, array $payload): array
    {
        $feeType = FinanceFeeType::forInstitution($institutionId)->findOrFail($payload['fee_type_id']);
        if (!$feeType->is_active) {
            throw ValidationException::withMessages([
                'fee_type_id' => 'Jenis biaya tidak aktif.',
            ]);
        }

        $hasStudents = !empty($payload['student_ids']) && is_array($payload['student_ids']);
        $hasClass = !empty($payload['class_id']);
        $allStudents = !empty($payload['all_students']);

        if (!$hasStudents && !$hasClass && !$allStudents) {
            throw ValidationException::withMessages([
                'target' => 'Pilih target: siswa, kelas, atau semua siswa aktif.',
            ]);
        }

        if ($feeType->scope === 'class' && !$hasClass && !$hasStudents) {
            throw ValidationException::withMessages([
                'class_id' => 'Jenis biaya ini memerlukan target kelas atau daftar siswa.',
            ]);
        }

        if ($feeType->scope === 'student' && !$hasStudents) {
            throw ValidationException::withMessages([
                'student_ids' => 'Jenis biaya ini memerlukan daftar siswa.',
            ]);
        }

        $students = $this->resolveStudents($institutionId, $payload);
        if ($students->isEmpty()) {
            throw ValidationException::withMessages([
                'student_ids' => 'Tidak ada siswa yang cocok untuk ditagih.',
            ]);
        }

        if ($hasStudents) {
            $requested = collect($payload['student_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            if ($requested->count() !== $students->count()) {
                throw ValidationException::withMessages([
                    'student_ids' => 'Sebagian siswa tidak ditemukan di institusi ini atau tidak aktif.',
                ]);
            }
        }

        $amount = isset($payload['amount']) ? (float) $payload['amount'] : (float) $feeType->default_amount;
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal tagihan harus lebih dari 0.',
            ]);
        }

        $title = trim((string) ($payload['title'] ?? $feeType->name));
        $periodLabel = $payload['period_label'] ?? null;

        if (in_array($feeType->frequency, ['monthly', 'yearly'], true) && empty($periodLabel)) {
            throw ValidationException::withMessages([
                'period_label' => $feeType->frequency === 'monthly'
                    ? 'Periode (YYYY-MM) wajib untuk biaya bulanan.'
                    : 'Periode wajib untuk biaya tahunan (YYYY atau YYYY-MM).',
            ]);
        }

        if ($periodLabel) {
            $ok = $feeType->frequency === 'yearly'
                ? (bool) preg_match('/^\d{4}(-\d{2})?$/', $periodLabel)
                : (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodLabel);
            if (!$ok) {
                throw ValidationException::withMessages([
                    'period_label' => $feeType->frequency === 'yearly'
                        ? 'Format periode tahunan harus YYYY atau YYYY-MM.'
                        : 'Format periode harus YYYY-MM (bulan 01–12).',
                ]);
            }
        }

        $batchKey = (string) Str::uuid();
        $created = 0;
        $skipped = 0;
        $invoices = collect();

        DB::transaction(function () use (
            $students,
            $institutionId,
            $feeType,
            $amount,
            $title,
            $periodLabel,
            $payload,
            $userId,
            $batchKey,
            &$created,
            &$skipped,
            &$invoices
        ) {
            foreach ($students as $student) {
                if ($this->hasActiveDuplicate($institutionId, $feeType, (int) $student->id, $periodLabel, $title)) {
                    $skipped++;
                    continue;
                }

                try {
                    $invoice = FinanceInvoice::create([
                        'institution_id' => $institutionId,
                        'fee_type_id' => $feeType->id,
                        'student_id' => $student->id,
                        'class_id' => $student->class_id,
                        'academic_year_id' => $payload['academic_year_id'] ?? $student->academic_year_id,
                        'title' => $title,
                        'period_label' => $periodLabel,
                        'amount' => $amount,
                        'amount_paid' => 0,
                        'due_date' => $payload['due_date'] ?? null,
                        'status' => 'unpaid',
                        'notes' => $payload['notes'] ?? null,
                        'batch_key' => $batchKey,
                        'created_by' => $userId,
                    ]);
                } catch (UniqueConstraintViolationException|QueryException $e) {
                    // Concurrent generate can race past the exists-check; unique index is the source of truth.
                    if ($this->isFinancePeriodUniqueViolation($e)) {
                        $skipped++;
                        continue;
                    }
                    throw $e;
                }
                $invoices->push($invoice);
                $created++;
            }
        });

        return [
            'created' => $created,
            'skipped' => $skipped,
            'batch_key' => $batchKey,
            'invoices' => $invoices,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordPayment(int $institutionId, int $userId, array $payload): FinancePayment
    {
        $amount = (float) $payload['amount'];
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        return DB::transaction(function () use ($institutionId, $userId, $payload, $amount) {
            $invoice = FinanceInvoice::forInstitution($institutionId)
                ->lockForUpdate()
                ->findOrFail($payload['invoice_id']);

            if ($invoice->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'invoice_id' => 'Tagihan sudah dibatalkan.',
                ]);
            }

            $paidSum = (float) $invoice->payments()->sum('amount');
            $remaining = max(0, (float) $invoice->amount - $paidSum);
            if ($amount - $remaining > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal melebihi sisa tagihan (Rp ' . number_format($remaining, 0, ',', '.') . ').',
                ]);
            }

            $payment = FinancePayment::create([
                'institution_id' => $institutionId,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'paid_at' => $payload['paid_at'] ?? now(),
                'method' => $payload['method'] ?? 'cash',
                'reference' => $payload['reference'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'recorded_by' => $userId,
            ]);

            $invoice->refreshPaymentStatus();

            return $payment->fresh(['invoice.student', 'invoice.feeType', 'invoice.schoolClass']);
        });
    }

    public function deletePayment(FinancePayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $invoice = FinanceInvoice::query()->lockForUpdate()->find($payment->invoice_id);
            $payment->delete();
            if ($invoice && $invoice->status !== 'cancelled') {
                $invoice->refreshPaymentStatus();
            }
        });
    }

    protected function hasActiveDuplicate(
        int $institutionId,
        FinanceFeeType $feeType,
        int $studentId,
        ?string $periodLabel,
        string $title
    ): bool {
        $query = FinanceInvoice::query()
            ->where('institution_id', $institutionId)
            ->where('fee_type_id', $feeType->id)
            ->where('student_id', $studentId)
            ->where('status', '!=', 'cancelled');

        if ($periodLabel) {
            return $query->where('period_label', $periodLabel)->exists();
        }

        // one_time / as_needed tanpa periode: cegah duplikat judul yang sama
        return $query
            ->whereNull('period_label')
            ->where('title', $title)
            ->exists();
    }

    protected function isFinancePeriodUniqueViolation(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        $message = $e->getMessage();

        $isDuplicate = $sqlState === '23000' || $driverCode === 1062 || str_contains($message, 'UNIQUE constraint failed');
        if (!$isDuplicate) {
            return false;
        }

        return str_contains($message, 'finance_invoices_period_unique')
            || str_contains($message, 'period_label');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return Collection<int, Student>
     */
    protected function resolveStudents(int $institutionId, array $payload): Collection
    {
        $query = Student::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif');

        if (!empty($payload['student_ids']) && is_array($payload['student_ids'])) {
            $query->whereIn('id', $payload['student_ids']);
        } elseif (!empty($payload['class_id'])) {
            $query->where('class_id', $payload['class_id']);
        } elseif (empty($payload['all_students'])) {
            return collect();
        }

        return $query->orderBy('name')->get();
    }
}
