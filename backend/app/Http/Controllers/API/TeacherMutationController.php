<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveTeacherMutationRequest;
use App\Http\Requests\CancelTeacherMutationRequest;
use App\Http\Requests\DecideCancelTeacherMutationRequest;
use App\Http\Requests\StoreTeacherMutationPullRequest;
use App\Http\Requests\StoreTeacherMutationRequest;
use App\Http\Resources\TeacherMutationResource;
use App\Models\Institution;
use App\Models\TeacherMutation;
use App\Services\TeacherMutationService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TeacherMutationController extends Controller
{
    public function __construct(
        protected TeacherMutationService $mutationService
    ) {}

    /**
     * Resolve active institution (header/cookie/home) for mutation APIs.
     */
    private function resolveInstitutionId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        return InstitutionContext::resolveForUser($user, $request);
    }

    /**
     * List mutation requests (as origin, as target, or both).
     */
    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['status', 'role']);
            $perPage = min($request->get('per_page', 15), 100);
            $list = $this->mutationService->listForInstitution($institutionId, $filters, $perPage);

            return TeacherMutationResource::collection($list);
        } catch (\Exception $e) {
            Log::error('Teacher mutation index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data permohonan mutasi guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create mutation request from origin school (NPSN tujuan + NUPTK guru).
     * Jika external=true: sekolah tujuan belum terdaftar, NPSN + nama dicatat manual, status langsung approved.
     */
    public function store(StoreTeacherMutationRequest $request)
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $external = $request->validated('external', false);

            if ($external) {
                $mutation = $this->mutationService->createFromOriginExternal(
                    $institutionId,
                    $request->validated('target_npsn'),
                    $request->validated('target_school_name'),
                    $request->validated('nuptk'),
                    $user->id,
                    $request->validated('notes')
                );
                $mutation->load([
                    'originInstitution:id,name,npsn,level',
                    'employee:id,nuptk,nip,name,gender,status,email',
                    'requester:id,name,email',
                    'approver:id,name',
                ]);
            } else {
                $mutation = $this->mutationService->createFromOrigin(
                    $institutionId,
                    $request->validated('target_npsn'),
                    $request->validated('nuptk'),
                    $user->id,
                    $request->validated('notes')
                );
                $mutation->load([
                    'originInstitution:id,name,npsn,level',
                    'targetInstitution:id,name,npsn,level',
                    'employee:id,nuptk,nip,name,gender,status,email',
                    'requester:id,name,email',
                ]);
            }

            return (new TeacherMutationResource($mutation))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Teacher mutation store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengajukan mutasi guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create mutation request from target school (tarik guru).
     * Jika external=true: sekolah asal belum terdaftar, input manual NPSN + nama asal + data guru; langsung approved.
     */
    public function pull(StoreTeacherMutationPullRequest $request)
    {
        try {
            $user = $request->user();
            $targetInstitutionId = $this->resolveInstitutionId($request);
            if (!$targetInstitutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $external = $request->validated('external', false);

            if ($external) {
                $mutation = $this->mutationService->createFromTargetExternal(
                    $targetInstitutionId,
                    $request->validated('origin_npsn'),
                    $request->validated('origin_school_name'),
                    $request->validated('employee_name'),
                    $request->validated('nuptk'),
                    $request->validated('employee_gender'),
                    $request->validated('employee_nip'),
                    $request->validated('employee_email'),
                    $user->id,
                    $request->validated('notes')
                );
                $mutation->load([
                    'targetInstitution:id,name,npsn,level',
                    'employee:id,nuptk,nip,name,gender,status,email',
                    'requester:id,name,email',
                    'approver:id,name',
                ]);
            } else {
                $mutation = $this->mutationService->createFromTarget(
                    $targetInstitutionId,
                    $request->validated('origin_npsn'),
                    $request->validated('nuptk'),
                    $user->id,
                    $request->validated('notes')
                );
                $mutation->load([
                    'originInstitution:id,name,npsn,level',
                    'targetInstitution:id,name,npsn,level',
                    'employee:id,nuptk,nip,name,gender,status,email',
                    'requester:id,name,email',
                ]);
            }

            return (new TeacherMutationResource($mutation))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Teacher mutation pull failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengajukan tarik guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single mutation request.
     */
    public function show(Request $request, TeacherMutation $teacher_mutation)
    {
        $user = $request->user();
        $instId = $this->resolveInstitutionId($request);
        $m = $teacher_mutation;
        if (
            !$user->isSuperAdmin()
            && (int) $instId !== (int) $m->origin_institution_id
            && (int) $instId !== (int) $m->target_institution_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teacher_mutation->load([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'employee:id,nuptk,nip,name,gender,status,email,institution_id',
            'requester:id,name,email',
            'approver:id,name',
        ]);

        return new TeacherMutationResource($teacher_mutation);
    }

    /**
     * Approve or reject mutation (destination admin for origin-initiated, origin admin for target-initiated).
     */
    public function approve(ApproveTeacherMutationRequest $request, TeacherMutation $teacher_mutation)
    {
        try {
            $user = $request->user();
            $action = $request->validated('action');
            $notes = $request->validated('notes');

            if ($action === 'approve') {
                $mutation = $this->mutationService->approve($teacher_mutation, $user->id, $notes);
                $message = 'Permohonan mutasi disetujui. Data guru telah dipindahkan ke sekolah tujuan.';
            } else {
                $mutation = $this->mutationService->reject(
                    $teacher_mutation,
                    $user->id,
                    $request->validated('rejection_reason'),
                    $notes
                );
                $message = 'Permohonan mutasi ditolak.';
            }

            return response()->json([
                'message' => $message,
                'data' => new TeacherMutationResource($mutation),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Teacher mutation approve/reject failed', [
                'id' => $teacher_mutation->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Gagal memproses permohonan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Batalkan mutasi (langsung jika pending/eksternal, atau ajukan batal jika sudah approved).
     */
    public function cancel(CancelTeacherMutationRequest $request, TeacherMutation $teacher_mutation)
    {
        try {
            $mutation = $this->mutationService->requestCancel(
                $teacher_mutation,
                $request->user(),
                $request->validated('reason')
            );

            $message = $mutation->status === 'cancel_pending'
                ? 'Permohonan pembatalan dikirim. Menunggu persetujuan admin sekolah tujuan.'
                : 'Permohonan mutasi berhasil dibatalkan.';

            return response()->json([
                'message' => $message,
                'data' => new TeacherMutationResource($mutation),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Teacher mutation cancel failed', [
                'id' => $teacher_mutation->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Gagal membatalkan permohonan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Setujui atau tolak permohonan pembatalan mutasi (admin sekolah tujuan).
     */
    public function decideCancel(DecideCancelTeacherMutationRequest $request, TeacherMutation $teacher_mutation)
    {
        try {
            $user = $request->user();
            $action = $request->validated('action');

            if ($action === 'approve') {
                $mutation = $this->mutationService->approveCancel(
                    $teacher_mutation,
                    $user,
                    $request->validated('notes')
                );
                $message = 'Pembatalan mutasi disetujui. Data guru dikembalikan ke sekolah asal.';
            } else {
                $mutation = $this->mutationService->rejectCancel(
                    $teacher_mutation,
                    $user,
                    $request->validated('rejection_reason')
                );
                $message = 'Permohonan pembatalan mutasi ditolak. Mutasi tetap berlaku.';
            }

            return response()->json([
                'message' => $message,
                'data' => new TeacherMutationResource($mutation),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Teacher mutation decide-cancel failed', [
                'id' => $teacher_mutation->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Gagal memproses pembatalan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Lookup teacher by NUPTK at current institution (preview before submitting mutation out).
     */
    public function lookupTeacher(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $nuptk = trim((string) $request->get('nuptk', ''));
            if ($nuptk === '') {
                return response()->json(['message' => 'NUPTK wajib diisi.'], 422);
            }

            $teacher = $this->mutationService->lookupTeacherByNuptk($institutionId, $nuptk);

            return response()->json(['data' => $teacher]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Teacher mutation lookup failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencari data guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Lookup teacher at origin school by NPSN + NUPTK (preview before pull).
     */
    public function lookupTeacherAtOrigin(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $originNpsn = trim((string) $request->get('origin_npsn', ''));
            $nuptk = trim((string) $request->get('nuptk', ''));
            if (strlen($originNpsn) !== 8) {
                return response()->json(['message' => 'NPSN sekolah asal harus 8 digit.'], 422);
            }
            if ($nuptk === '') {
                return response()->json(['message' => 'NUPTK wajib diisi.'], 422);
            }

            $teacher = $this->mutationService->lookupTeacherAtOriginByNpsn($originNpsn, $nuptk, $institutionId);

            return response()->json(['data' => $teacher]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Teacher mutation lookup at origin failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencari data guru di sekolah asal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get institutions by NPSN search (for dropdown/autocomplete). Tidak ada batasan jenjang untuk guru.
     */
    public function searchTargetInstitutions(Request $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $search = $request->get('q', '');
        $query = Institution::where('id', '!=', $institutionId)
            ->where('is_active', true);

        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('npsn', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $list = $query->select(['id', 'name', 'npsn', 'level'])
            ->orderBy('npsn')
            ->limit(20)
            ->get();

        return response()->json(['data' => $list]);
    }

    /**
     * Get institutions by NPSN search for "origin" (sekolah asal) when target admin tarik guru.
     * Tidak ada batasan jenjang untuk guru; exclude self.
     */
    public function searchOriginInstitutions(Request $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $search = $request->get('q', '');
        $query = Institution::where('id', '!=', $institutionId)
            ->where('is_active', true);

        if (strlen($search) >= 2) {
            $query->where(function ($q) use ($search) {
                $q->where('npsn', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $list = $query->select(['id', 'name', 'npsn', 'level'])
            ->orderBy('npsn')
            ->limit(20)
            ->get();

        return response()->json(['data' => $list]);
    }

    /**
     * Laporan mutasi guru: keluar/masuk per periode untuk institusi user.
     */
    public function report(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $from = $request->get('from');
            $to = $request->get('to');
            $type = $request->get('type', 'all');
            if (!in_array($type, ['out', 'in', 'all'], true)) {
                $type = 'all';
            }
            $perPage = min($request->get('per_page', 15), 100);

            $result = $this->mutationService->reportForInstitution($institutionId, $from, $to, $type, $perPage);

            return response()->json([
                'summary' => $result['summary'],
                'data' => TeacherMutationResource::collection($result['data']),
                'meta' => [
                    'current_page' => $result['data']->currentPage(),
                    'last_page' => $result['data']->lastPage(),
                    'per_page' => $result['data']->perPage(),
                    'total' => $result['data']->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Teacher mutation report failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil laporan mutasi guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export Buku Mutasi Guru: PDF atau CSV (filter from, to, type sama seperti report).
     */
    public function export(Request $request): Response|StreamedResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $from = $request->get('from');
            $to = $request->get('to');
            $type = $request->get('type', 'all');
            if (!in_array($type, ['out', 'in', 'all'], true)) {
                $type = 'all';
            }
            $format = $request->get('format', 'pdf');
            if (!in_array($format, ['pdf', 'csv'], true)) {
                $format = 'pdf';
            }

            $institution = Institution::find($institutionId);
            $mutations = $this->mutationService->listForExport($institutionId, $from, $to, $type, 2000);

            if ($format === 'csv') {
                $filename = 'Buku_Mutasi_Guru_' . date('Y-m-d_His') . '.csv';
                return response()->streamDownload(function () use ($mutations, $institutionId) {
                    $out = fopen('php://output', 'w');
                    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    fputcsv($out, [
                        'No', 'Tanggal', 'NUPTK', 'NIP', 'Nama Guru', 'JK', 'Jenis', 'Sekolah Asal', 'NPSN Asal',
                        'Sekolah Tujuan', 'NPSN Tujuan', 'Keterangan', 'Disetujui oleh',
                    ]);
                    foreach ($mutations as $idx => $m) {
                        $isOut = (int) $m->origin_institution_id === (int) $institutionId;
                        $jenis = $isOut ? 'Keluar' : 'Masuk';
                        $tanggal = $m->approved_at ? $m->approved_at->format('Y-m-d') : ($m->created_at ? $m->created_at->format('Y-m-d') : '');
                        $targetName = $m->targetInstitution?->name ?? $m->target_school_name ?? '';
                        $targetNpsn = $m->targetInstitution?->npsn ?? $m->target_npsn ?? '';
                        fputcsv($out, [
                            $idx + 1,
                            $tanggal,
                            $m->employee_nuptk ?? $m->employee?->nuptk ?? '',
                            $m->employee_nip ?? $m->employee?->nip ?? '',
                            $m->employee?->name ?? '',
                            $m->employee_gender ?? $m->employee?->gender ?? '',
                            $jenis,
                            $m->originInstitution?->name ?? $m->origin_school_name ?? '',
                            $m->originInstitution?->npsn ?? $m->origin_npsn ?? '',
                            $targetName,
                            $targetNpsn,
                            $m->notes ?? '',
                            $m->approver?->name ?? '',
                        ]);
                    }
                    fclose($out);
                }, $filename, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                ]);
            }

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $data = [
                'institution' => $institution,
                'institution_id' => $institutionId,
                'mutations' => $mutations,
                'date_from' => $from,
                'date_to' => $to,
                'printed_at' => $printedAt,
            ];
            $pdf = DomPDF::loadView('buku_mutasi_guru.print', $data);
            $pdfFilename = 'Buku_Mutasi_Guru_' . date('Y-m-d_His') . '.pdf';
            // Inline stream agar frontend bisa preview di tab baru (bukan force-download).
            return $pdf->stream($pdfFilename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('Teacher mutation export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor Buku Mutasi Guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Riwayat mutasi per guru (hanya jika institusi user terlibat).
     */
    public function historyByEmployee(Request $request, int $employee_id)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $list = $this->mutationService->historyForEmployee($employee_id, $institutionId);

            return TeacherMutationResource::collection($list);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Teacher mutation history failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat mutasi guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Riwayat mutasi per guru by NUPTK.
     */
    public function historyByNuptk(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $nuptk = $request->get('nuptk');
            if (!$nuptk || !is_string($nuptk)) {
                return response()->json(['message' => 'NUPTK wajib diisi.'], 422);
            }
            $list = $this->mutationService->historyByNuptk(trim($nuptk), $institutionId);
            return TeacherMutationResource::collection($list);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Teacher mutation history by NUPTK failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat mutasi guru.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
