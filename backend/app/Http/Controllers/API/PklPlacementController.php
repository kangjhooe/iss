<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\IndustryPartner;
use App\Models\PklMonitoringLog;
use App\Models\PklPeriod;
use App\Models\PklPlacement;
use App\Models\Student;
use App\Support\KaprogAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PklPlacementController extends Controller
{
    use ResolvesInstitution;

    public function index(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $query = $this->filteredQuery($request, $institutionId)
                ->with([
                    'student:id,name,nis,nisn,class_id',
                    'student.class:id,name',
                    'industryPartner:id,name,city',
                    'supervisor:id,name',
                    'period:id,name,status',
                ])
                ->withCount(['monitoringLogs', 'journals'])
                ->orderByDesc('id');

            $perPage = min((int) $request->get('per_page', 20), 100);

            return response()->json($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('PklPlacement index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil penempatan PKL.'], 500);
        }
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $rows = $this->filteredQuery($request, $institutionId)
            ->with([
                'student:id,name,nis,nisn,class_id',
                'student.class:id,name',
                'industryPartner:id,name,city',
                'supervisor:id,name',
                'period:id,name',
            ])
            ->withCount(['monitoringLogs', 'journals'])
            ->orderBy('pkl_period_id')
            ->orderBy('id')
            ->get();

        $filename = 'pkl-penempatan-' . date('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [
                'No', 'Periode', 'Nama Siswa', 'NIS', 'NISN', 'Kelas', 'Mitra', 'Kota Mitra',
                'Pembimbing Sekolah', 'Pembimbing Industri', 'Mulai', 'Selesai', 'Status',
                'Nilai', 'Catatan Penilaian', 'Catatan', 'Jml Monitoring', 'Jml Jurnal',
            ]);
            $no = 1;
            foreach ($rows as $p) {
                fputcsv($out, [
                    $no++,
                    $p->period?->name ?? '-',
                    $p->student?->name ?? '-',
                    $p->student?->nis ?? '-',
                    $p->student?->nisn ?? '-',
                    $p->student?->class?->name ?? '-',
                    $p->industryPartner?->name ?? '-',
                    $p->industryPartner?->city ?? '-',
                    $p->supervisor?->name ?? '-',
                    $p->industry_supervisor_name ?? '-',
                    $p->start_date?->format('Y-m-d') ?? '-',
                    $p->end_date?->format('Y-m-d') ?? '-',
                    $p->status ?? '-',
                    $p->score ?? '-',
                    $p->assessment_notes ?? '',
                    $p->notes ?? '',
                    $p->monitoring_logs_count ?? 0,
                    $p->journals_count ?? 0,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function bulkStore(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $data = $request->validate([
            'pkl_period_id' => 'required|integer|exists:pkl_periods,id',
            'industry_partner_id' => 'required|integer|exists:industry_partners,id',
            'student_ids' => 'required|array|min:1|max:200',
            'student_ids.*' => 'integer|exists:student,id',
            'supervisor_employee_id' => 'nullable|integer|exists:employee,id',
            'industry_supervisor_name' => 'nullable|string|max:150',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', Rule::in(PklPlacement::STATUSES)],
        ]);

        $period = PklPeriod::findOrFail($data['pkl_period_id']);
        if ((int) $period->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Periode PKL tidak valid untuk institusi ini.'], 422);
        }

        $partner = IndustryPartner::findOrFail($data['industry_partner_id']);
        if ((int) $partner->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Mitra DU/DI tidak valid untuk institusi ini.'], 422);
        }

        $studentIds = array_values(array_unique($data['student_ids']));
        $students = Student::whereIn('id', $studentIds)
            ->where('institution_id', $institutionId)
            ->pluck('id')
            ->all();

        if (count($students) !== count($studentIds)) {
            return response()->json(['message' => 'Beberapa siswa tidak valid untuk institusi ini.'], 422);
        }

        $already = PklPlacement::where('pkl_period_id', $data['pkl_period_id'])
            ->whereIn('student_id', $studentIds)
            ->pluck('student_id')
            ->all();

        $toCreate = array_values(array_diff($studentIds, $already));
        $status = $data['status'] ?? 'draft';
        $created = 0;

        DB::transaction(function () use ($toCreate, $data, $institutionId, $status, &$created) {
            foreach ($toCreate as $studentId) {
                PklPlacement::create([
                    'institution_id' => $institutionId,
                    'pkl_period_id' => $data['pkl_period_id'],
                    'student_id' => $studentId,
                    'industry_partner_id' => $data['industry_partner_id'],
                    'supervisor_employee_id' => $data['supervisor_employee_id'] ?? null,
                    'industry_supervisor_name' => $data['industry_supervisor_name'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                    'status' => $status,
                ]);
                $created++;
            }
        });

        return response()->json([
            'message' => "Berhasil menempatkan {$created} siswa."
                . (count($already) ? ' ' . count($already) . ' dilewati (sudah terdaftar).' : ''),
            'data' => [
                'created' => $created,
                'skipped' => count($already),
                'skipped_student_ids' => $already,
            ],
        ], 201);
    }

    public function store(Request $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $data = $request->validate([
            'pkl_period_id' => 'required|integer|exists:pkl_periods,id',
            'student_id' => 'required|integer|exists:student,id',
            'industry_partner_id' => 'required|integer|exists:industry_partners,id',
            'supervisor_employee_id' => 'nullable|integer|exists:employee,id',
            'industry_supervisor_name' => 'nullable|string|max:150',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', Rule::in(PklPlacement::STATUSES)],
            'score' => 'nullable|numeric|min:0|max:100',
            'assessment_notes' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
        ]);

        $period = PklPeriod::findOrFail($data['pkl_period_id']);
        if ((int) $period->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Periode PKL tidak valid untuk institusi ini.'], 422);
        }

        $partner = IndustryPartner::findOrFail($data['industry_partner_id']);
        if ((int) $partner->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Mitra DU/DI tidak valid untuk institusi ini.'], 422);
        }

        $student = Student::findOrFail($data['student_id']);
        if ((int) $student->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Siswa tidak berada di institusi Anda.'], 422);
        }

        $exists = PklPlacement::where('pkl_period_id', $data['pkl_period_id'])
            ->where('student_id', $data['student_id'])
            ->exists();
        if ($exists) {
            return response()->json(['message' => 'Siswa sudah ditempatkan pada periode ini.'], 422);
        }

        $data['institution_id'] = $institutionId;
        $data['status'] = $data['status'] ?? 'draft';

        $placement = PklPlacement::create($data);
        $placement->load([
            'student:id,name,nis,nisn,class_id',
            'student.class:id,name',
            'industryPartner:id,name,city',
            'supervisor:id,name',
            'period:id,name,status',
        ]);

        return response()->json([
            'message' => 'Penempatan PKL berhasil ditambahkan.',
            'data' => $placement,
        ], 201);
    }

    public function show(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        $pkl_placement->load([
            'student:id,name,nis,nisn,class_id',
            'student.class:id,name',
            'industryPartner',
            'supervisor:id,name',
            'period:id,name,status,start_date,end_date',
            'monitoringLogs.loggedBy:id,name',
        ]);

        return response()->json(['data' => $pkl_placement]);
    }

    public function update(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        $data = $request->validate([
            'industry_partner_id' => 'sometimes|integer|exists:industry_partners,id',
            'supervisor_employee_id' => 'nullable|integer|exists:employee,id',
            'industry_supervisor_name' => 'nullable|string|max:150',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => ['nullable', Rule::in(PklPlacement::STATUSES)],
            'score' => 'nullable|numeric|min:0|max:100',
            'assessment_notes' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
        ]);

        if (!empty($data['industry_partner_id'])) {
            $partner = IndustryPartner::findOrFail($data['industry_partner_id']);
            if ((int) $partner->institution_id !== (int) $pkl_placement->institution_id) {
                return response()->json(['message' => 'Mitra DU/DI tidak valid.'], 422);
            }
        }

        $pkl_placement->update($data);
        $pkl_placement->load([
            'student:id,name,nis,nisn,class_id',
            'student.class:id,name',
            'industryPartner:id,name,city',
            'supervisor:id,name',
            'period:id,name,status',
        ]);

        return response()->json([
            'message' => 'Penempatan PKL berhasil diperbarui.',
            'data' => $pkl_placement,
        ]);
    }

    public function destroy(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        $pkl_placement->monitoringLogs()->delete();
        $pkl_placement->delete();

        return response()->json(['message' => 'Penempatan PKL berhasil dihapus.']);
    }

    public function listMonitoring(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        $logs = $pkl_placement->monitoringLogs()->with('loggedBy:id,name')->get();

        return response()->json(['data' => $logs]);
    }

    public function storeMonitoring(Request $request, PklPlacement $pkl_placement): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        $data = $request->validate([
            'visit_date' => 'required|date',
            'method' => ['nullable', Rule::in(PklMonitoringLog::METHODS)],
            'notes' => 'nullable|string|max:5000',
        ]);

        $employeeId = $request->user()->employeeProfile?->id;

        $log = PklMonitoringLog::create([
            'institution_id' => $pkl_placement->institution_id,
            'pkl_placement_id' => $pkl_placement->id,
            'logged_by_employee_id' => $employeeId,
            'visit_date' => $data['visit_date'],
            'method' => $data['method'] ?? 'kunjungan',
            'notes' => $data['notes'] ?? null,
        ]);
        $log->load('loggedBy:id,name');

        return response()->json([
            'message' => 'Catatan monitoring berhasil ditambahkan.',
            'data' => $log,
        ], 201);
    }

    public function destroyMonitoring(Request $request, PklPlacement $pkl_placement, PklMonitoringLog $monitoring_log): JsonResponse
    {
        if ($denied = $this->denyOutside($request, $pkl_placement->institution_id)) {
            return $denied;
        }

        if ((int) $monitoring_log->pkl_placement_id !== (int) $pkl_placement->id) {
            return response()->json(['message' => 'Catatan tidak ditemukan.'], 404);
        }

        $monitoring_log->delete();

        return response()->json(['message' => 'Catatan monitoring dihapus.']);
    }

    private function filteredQuery(Request $request, int $institutionId)
    {
        $query = PklPlacement::forInstitution($institutionId)
            ->when($request->filled('pkl_period_id'), fn ($q) => $q->where('pkl_period_id', $request->get('pkl_period_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->get('status')))
            ->when($request->filled('industry_partner_id'), fn ($q) => $q->where('industry_partner_id', $request->get('industry_partner_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $s = '%' . $request->get('search') . '%';
                $q->whereHas('student', function ($sq) use ($s) {
                    $sq->where('name', 'like', $s)
                        ->orWhere('nis', 'like', $s)
                        ->orWhere('nisn', 'like', $s);
                });
            });

        $user = $request->user();
        if ($user && KaprogAccess::shouldScope($user)) {
            $programIds = KaprogAccess::programIds($user);
            if ($programIds === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('student.class', function ($cq) use ($programIds) {
                    $cq->whereIn('program_keahlian_id', $programIds);
                });
            }
        }

        return $query;
    }

    private function denyOutside(Request $request, int $recordInstitutionId): ?JsonResponse
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return null;
        }
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId || (int) $recordInstitutionId !== (int) $institutionId) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return null;
    }
}
