<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PpdbChannelResource;
use App\Http\Resources\PpdbPeriodResource;
use App\Models\PpdbApplicant;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PpdbDashboardController extends Controller
{
    /**
     * Ringkasan dashboard PPDB untuk institusi aktif.
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if ($user->isSuperAdmin() && $request->filled('institution_id')) {
                $institutionId = (int) $request->institution_id;
            }
            if (!$institutionId && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            if (!$institutionId) {
                return response()->json(['message' => 'Pilih institusi.'], 403);
            }

            $periods = PpdbPeriod::forInstitution($institutionId)
                ->with('academicYear:id,code,name')
                ->withCount('applicants')
                ->orderByDesc('open_date')
                ->get();

            $activePeriod = $periods->firstWhere('status', 'open') ?? $periods->first();

            $channels = PpdbChannel::forInstitution($institutionId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $base = PpdbApplicant::query()
                ->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId));

            $total = (clone $base)->count();
            $needsVerification = (clone $base)->whereIn('status', ['submitted', 'verification'])->count();
            $needsResult = (clone $base)->where('status', 'verified')->count();
            $unpaid = (clone $base)->whereIn('payment_status', ['unpaid', 'pending'])->count();
            $paid = (clone $base)->where('payment_status', 'paid')->count();

            $byStatus = (clone $base)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            return response()->json([
                'data' => [
                    'active_period' => $activePeriod
                        ? new PpdbPeriodResource($activePeriod)
                        : null,
                    'periods_count' => $periods->count(),
                    'channels' => PpdbChannelResource::collection($channels),
                    'totals' => [
                        'total' => $total,
                        'needs_verification' => $needsVerification,
                        'needs_result' => $needsResult,
                        'unpaid' => $unpaid,
                        'paid' => $paid,
                    ],
                    'by_status' => $byStatus,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Ppdb summary failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memuat ringkasan PPDB.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
