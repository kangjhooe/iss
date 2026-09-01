<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\FinanceInvoiceResource;
use App\Http\Resources\FinancePaymentResource;
use App\Models\FinanceInvoice;
use App\Models\FinancePayment;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class StudentFinanceController extends Controller
{
    /**
     * @return array{0: User, 1: Student, 2: int}|JsonResponse
     */
    protected function resolveStudentContext(Request $request): array|JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isStudent()) {
            return response()->json(['message' => 'Hanya siswa yang dapat mengakses data ini.'], 403);
        }

        $student = $user->studentProfile;
        if (!$student) {
            return response()->json(['message' => 'Profil siswa tidak ditemukan untuk akun ini.'], 404);
        }

        $institutionId = (int) ($student->institution_id ?: $user->institution_id);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        return [$user, $student, $institutionId];
    }

    /**
     * Ringkasan tagihan siswa yang login.
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            $base = FinanceInvoice::forInstitution($institutionId)
                ->where('student_id', $student->id)
                ->where('status', '!=', 'cancelled');

            $outstanding = (clone $base)->outstanding()->get(['amount', 'amount_paid']);
            $outstandingRemaining = $outstanding->sum(fn ($inv) => max(0, (float) $inv->amount - (float) $inv->amount_paid));

            $billed = (float) (clone $base)->sum('amount');
            $paid = (float) (clone $base)->sum('amount_paid');
            $unpaidCount = (clone $base)->where('status', 'unpaid')->count();
            $partialCount = (clone $base)->where('status', 'partial')->count();
            $paidCount = (clone $base)->where('status', 'paid')->count();

            return response()->json([
                'data' => [
                    'billed' => $billed,
                    'paid' => $paid,
                    'outstanding' => $outstandingRemaining,
                    'counts' => [
                        'unpaid' => $unpaidCount,
                        'partial' => $partialCount,
                        'paid' => $paidCount,
                        'outstanding' => $unpaidCount + $partialCount,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('StudentFinance summary failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil ringkasan tagihan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Daftar tagihan siswa yang login.
     */
    public function invoices(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            $query = FinanceInvoice::forInstitution($institutionId)
                ->where('student_id', $student->id)
                ->with(['feeType:id,name,code,frequency', 'schoolClass:id,name,code'])
                ->orderByDesc('created_at');

            if ($request->filled('status')) {
                if ($request->get('status') === 'outstanding') {
                    $query->outstanding();
                } else {
                    $query->where('status', $request->get('status'));
                }
            } elseif (!$request->boolean('include_cancelled')) {
                $query->where('status', '!=', 'cancelled');
            }

            $perPage = min((int) $request->get('per_page', 50), 100);

            return FinanceInvoiceResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('StudentFinance invoices failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil daftar tagihan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Riwayat pembayaran siswa yang login.
     */
    public function payments(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            $query = FinancePayment::forInstitution($institutionId)
                ->whereHas('invoice', function ($q) use ($student) {
                    $q->where('student_id', $student->id)
                        ->where('status', '!=', 'cancelled');
                })
                ->with([
                    'invoice:id,title,status,amount,amount_paid,fee_type_id,student_id,class_id',
                    'invoice.feeType:id,name',
                    'invoice.schoolClass:id,name',
                ])
                ->orderByDesc('paid_at');

            if ($request->filled('method')) {
                $query->where('method', $request->get('method'));
            }

            $perPage = min((int) $request->get('per_page', 50), 100);

            return FinancePaymentResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('StudentFinance payments failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil riwayat pembayaran.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cetak kwitansi PDF milik siswa yang login.
     */
    public function receipt(Request $request, FinancePayment $payment)
    {
        try {
            $ctx = $this->resolveStudentContext($request);
            if ($ctx instanceof JsonResponse) {
                return $ctx;
            }
            [, $student, $institutionId] = $ctx;

            $payment->load([
                'invoice.student:id,name,nis',
                'invoice.feeType:id,name',
                'invoice.schoolClass:id,name',
                'recorder:id,name',
            ]);

            if ((int) $payment->institution_id !== $institutionId
                || (int) ($payment->invoice?->student_id) !== (int) $student->id) {
                return response()->json(['message' => 'Anda hanya dapat melihat kwitansi milik sendiri.'], 403);
            }

            $institution = Institution::find($payment->institution_id);
            if (!$institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 404);
            }

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $asOfDate = $payment->paid_at ?? now();
            $pdf = DomPDF::loadView('finance.receipt', [
                'institution' => $institution,
                'payment' => $payment,
                'printed_at' => $printedAt,
                'as_of_date' => $asOfDate,
            ])->setPaper('a4', 'portrait');

            $filename = 'Kwitansi_' . $payment->id . '_' . date('Ymd_His') . '.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('StudentFinance receipt failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mencetak kwitansi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
