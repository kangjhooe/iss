<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StorePpdbPeriodRequest;
use App\Http\Requests\UpdatePpdbPeriodRequest;
use App\Http\Resources\PpdbPeriodResource;
use App\Models\PpdbPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class PpdbPeriodController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = PpdbPeriod::forInstitution($institutionId)
                ->with('academicYear:id,code,name')
                ->withCount('applicants')
                ->orderByDesc('open_date');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('academic_year_id')) {
                $query->where('academic_year_id', $request->academic_year_id);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $periods = $query->paginate($perPage);

            return PpdbPeriodResource::collection($periods);
        } catch (\Exception $e) {
            Log::error('PpdbPeriod index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data periode PPDB.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StorePpdbPeriodRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $data['institution_id'] = $institutionId;
            $data['status'] = $data['status'] ?? 'draft';
            $period = PpdbPeriod::create($data);

            return (new PpdbPeriodResource($period->load('academicYear')))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('PpdbPeriod store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan periode PPDB.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, PpdbPeriod $ppdb_period): PpdbPeriodResource|JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $ppdb_period->institution_id, 'Unauthorized')) {
            return $resp;
        }
        $ppdb_period->load(['academicYear', 'institution']);
        $ppdb_period->loadCount('applicants');
        return new PpdbPeriodResource($ppdb_period);
    }

    public function update(UpdatePpdbPeriodRequest $request, PpdbPeriod $ppdb_period): PpdbPeriodResource|JsonResponse
    {
        try {
            if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $ppdb_period->institution_id, 'Unauthorized')) {
                return $resp;
            }

            $ppdb_period->update($request->validated());
            return new PpdbPeriodResource($ppdb_period->fresh(['academicYear']));
        } catch (\Exception $e) {
            Log::error('PpdbPeriod update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui periode PPDB.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, PpdbPeriod $ppdb_period): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $ppdb_period->institution_id, 'Unauthorized')) {
            return $resp;
        }

        if ($ppdb_period->applicants()->exists()) {
            return response()->json([
                'message' => 'Periode tidak dapat dihapus karena sudah ada calon peserta didik.',
            ], 422);
        }

        $ppdb_period->delete();
        return response()->json(['message' => 'Periode PPDB berhasil dihapus.']);
    }

    /**
     * Statistik calon per periode: total, per jalur, per status.
     */
    public function statistics(Request $request, PpdbPeriod $ppdb_period): JsonResponse
    {
        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $ppdb_period->institution_id, 'Unauthorized')) {
            return $resp;
        }

        $total = $ppdb_period->applicants()->count();

        $byStatus = $ppdb_period->applicants()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        $byChannel = $ppdb_period->applicants()
            ->join('ppdb_channels', 'ppdb_applicants.ppdb_channel_id', '=', 'ppdb_channels.id')
            ->selectRaw('ppdb_channels.id as channel_id, ppdb_channels.name as channel_name, count(ppdb_applicants.id) as count')
            ->groupBy('ppdb_channels.id', 'ppdb_channels.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['channel_id' => $row->channel_id, 'channel_name' => $row->channel_name, 'count' => (int) $row->count])
            ->values()
            ->all();

        $byDay = $ppdb_period->applicants()
            ->get(['created_at', 'submitted_at'])
            ->map(function ($row) {
                $at = $row->submitted_at ?: $row->created_at;
                return $at ? \Carbon\Carbon::parse($at)->toDateString() : null;
            })
            ->filter()
            ->countBy()
            ->sortKeys()
            ->map(fn ($count, $date) => ['date' => $date, 'count' => (int) $count])
            ->values()
            ->all();

        return response()->json([
            'data' => [
                'period' => [
                    'id' => $ppdb_period->id,
                    'name' => $ppdb_period->name,
                ],
                'total' => $total,
                'by_status' => $byStatus,
                'by_channel' => $byChannel,
                'by_day' => $byDay,
            ],
        ]);
    }
}
