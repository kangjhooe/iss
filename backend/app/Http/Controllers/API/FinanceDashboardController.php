<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FinanceFeeType;
use App\Models\FinanceInvoice;
use App\Models\FinancePayment;
use App\Support\InstitutionContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinanceDashboardController extends Controller
{
    protected function institutionId(Request $request): ?int
    {
        $user = $request->user();
        return InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
    }

    public function summary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->institutionId($request);
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 400);
            }

            $invoiceBase = FinanceInvoice::forInstitution($institutionId)->where('status', '!=', 'cancelled');
            $billed = (clone $invoiceBase)->sum('amount');
            $collected = (clone $invoiceBase)->sum('amount_paid');
            $outstanding = max(0, (float) $billed - (float) $collected);

            $byStatus = FinanceInvoice::forInstitution($institutionId)
                ->select('status', DB::raw('count(*) as total'), DB::raw('sum(amount) as amount'), DB::raw('sum(amount_paid) as amount_paid'))
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            $byFeeTypeRows = FinanceInvoice::forInstitution($institutionId)
                ->where('status', '!=', 'cancelled')
                ->select(
                    'fee_type_id',
                    DB::raw('count(*) as total'),
                    DB::raw('sum(amount) as amount'),
                    DB::raw('sum(amount_paid) as amount_paid')
                )
                ->groupBy('fee_type_id')
                ->get();

            $feeTypes = FinanceFeeType::whereIn('id', $byFeeTypeRows->pluck('fee_type_id'))->get()->keyBy('id');
            $byFeeType = $byFeeTypeRows->map(fn ($row) => [
                'fee_type_id' => $row->fee_type_id,
                'fee_type' => ($ft = $feeTypes->get($row->fee_type_id)) ? [
                    'id' => $ft->id,
                    'name' => $ft->name,
                    'code' => $ft->code,
                    'frequency' => $ft->frequency,
                ] : null,
                'total' => (int) $row->total,
                'amount' => (float) $row->amount,
                'amount_paid' => (float) $row->amount_paid,
                'remaining' => max(0, (float) $row->amount - (float) $row->amount_paid),
            ]);

            $paymentsQuery = FinancePayment::forInstitution($institutionId)
                ->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'cancelled'));
            if ($request->filled('from')) {
                $paymentsQuery->whereDate('paid_at', '>=', $request->get('from'));
            }
            if ($request->filled('to')) {
                $paymentsQuery->whereDate('paid_at', '<=', $request->get('to'));
            }

            $paymentsTotal = (clone $paymentsQuery)->sum('amount');
            $paymentsCount = (clone $paymentsQuery)->count();
            $paymentsByMonth = $this->paymentsByMonth($institutionId, $request->get('from'), $request->get('to'));

            $feeTypesCount = FinanceFeeType::forInstitution($institutionId)->active()->count();
            $arrearsCount = FinanceInvoice::forInstitution($institutionId)->outstanding()->count();

            return response()->json([
                'data' => [
                    'fee_types_active' => $feeTypesCount,
                    'billed' => (float) $billed,
                    'collected' => (float) $collected,
                    'outstanding' => (float) $outstanding,
                    'arrears_count' => $arrearsCount,
                    'payments_in_range' => [
                        'count' => $paymentsCount,
                        'amount' => (float) $paymentsTotal,
                    ],
                    'payments_by_month' => $paymentsByMonth,
                    'by_status' => $byStatus,
                    'by_fee_type' => $byFeeType,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Finance summary failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil ringkasan keuangan.'], 500);
        }
    }

    /**
     * @return list<array{month:string,label:string,amount:float,count:int}>
     */
    protected function paymentsByMonth(int $institutionId, mixed $from, mixed $to): array
    {
        $end = $to ? Carbon::parse($to)->endOfMonth() : now()->endOfMonth();
        $start = $from ? Carbon::parse($from)->startOfMonth() : $end->copy()->startOfMonth()->subMonths(11);

        if ($start->gt($end)) {
            [$start, $end] = [$end->copy()->startOfMonth(), $start->copy()->endOfMonth()];
        }

        $rows = FinancePayment::forInstitution($institutionId)
            ->whereHas('invoice', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->whereDate('paid_at', '>=', $start->toDateString())
            ->whereDate('paid_at', '<=', $end->toDateString())
            ->get(['amount', 'paid_at']);

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $bucket = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m');
            $bucket[$key] = [
                'month' => $key,
                'label' => $monthNames[(int) $cursor->format('n') - 1] . ' ' . $cursor->format('Y'),
                'amount' => 0.0,
                'count' => 0,
            ];
            $cursor->addMonth();
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->paid_at)->format('Y-m');
            if (!isset($bucket[$key])) {
                continue;
            }
            $bucket[$key]['amount'] += (float) $row->amount;
            $bucket[$key]['count']++;
        }

        return array_values($bucket);
    }
}
