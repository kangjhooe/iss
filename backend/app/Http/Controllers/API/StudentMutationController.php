<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveStudentMutationRequest;
use App\Http\Requests\CancelStudentMutationRequest;
use App\Http\Requests\DecideCancelStudentMutationRequest;
use App\Http\Requests\StoreStudentMutationPullRequest;
use App\Http\Requests\StoreStudentMutationRequest;
use App\Http\Resources\StudentMutationResource;
use App\Models\Institution;
use App\Models\StudentMutation;
use App\Services\StudentMutationService;
use App\Support\InstitutionContext;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Http\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class StudentMutationController extends Controller
{
    public function __construct(
        protected StudentMutationService $mutationService
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

            return StudentMutationResource::collection($list);
        } catch (\Exception $e) {
            Log::error('Student mutation index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data permohonan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create mutation request from origin school (NPSN tujuan + NIK siswa).
     * Jika external=true: sekolah tujuan belum terdaftar, NPSN + nama dicatat manual, status langsung approved.
     */
    public function store(StoreStudentMutationRequest $request)
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
                    $request->validated('nik'),
                    $user->id,
                    $request->validated('notes')
                );
                $mutation->load([
                    'originInstitution:id,name,npsn,level',
                    'student:id,nik,nisn,nis,name,gender,status,deleted_at',
                    'requester:id,name,email',
                    'approver:id,name',
                ]);
            } else {
                $mutation = $this->mutationService->createFromOrigin(
                    $institutionId,
                    $request->validated('target_npsn'),
                    $request->validated('nik'),
                    $user->id,
                    $request->validated('notes')
                );
                $mutation->load([
                    'originInstitution:id,name,npsn,level',
                    'targetInstitution:id,name,npsn,level',
                    'student:id,nik,nisn,nis,name,gender,status,deleted_at',
                    'requester:id,name,email',
                ]);
            }

            return (new StudentMutationResource($mutation))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Student mutation store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengajukan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create mutation request from target school (tarik siswa).
     * Jika external=true: sekolah asal belum terdaftar, input manual NPSN + nama asal + data siswa; langsung approved.
     */
    public function pull(StoreStudentMutationPullRequest $request)
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
                    $request->validated('student_name'),
                    $request->validated('nik'),
                    $request->validated('student_gender'),
                    $request->validated('student_grade'),
                    $user->id,
                    $request->validated('notes'),
                    $request->validated('nisn')
                );
                $mutation->load([
                    'targetInstitution:id,name,npsn,level',
                    'student:id,nik,nisn,nis,name,gender,status,deleted_at',
                    'requester:id,name,email',
                    'approver:id,name',
                ]);
            } else {
                $mutation = $this->mutationService->createFromTarget(
                    $targetInstitutionId,
                    $request->validated('origin_npsn'),
                    $request->validated('nik'),
                    $user->id,
                    $request->validated('notes')
                );
                $mutation->load([
                    'originInstitution:id,name,npsn,level',
                    'targetInstitution:id,name,npsn,level',
                    'student:id,nik,nisn,nis,name,gender,status,deleted_at',
                    'requester:id,name,email',
                ]);
            }

            return (new StudentMutationResource($mutation))
                ->response()
                ->setStatusCode(201);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Student mutation pull failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengajukan tarik siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Show single mutation request.
     */
    public function show(Request $request, StudentMutation $student_mutation)
    {
        $user = $request->user();
        $instId = $this->resolveInstitutionId($request);
        $m = $student_mutation;
        if (
            !$user->isSuperAdmin()
            && (int) $instId !== (int) $m->origin_institution_id
            && (int) $instId !== (int) $m->target_institution_id
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student_mutation->load([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'student:id,nik,nisn,nis,name,gender,status,institution_id,deleted_at',
            'requester:id,name,email',
            'approver:id,name',
        ]);

        return new StudentMutationResource($student_mutation);
    }

    /**
     * Approve or reject mutation (destination admin for origin-initiated, origin admin for target-initiated).
     */
    public function approve(ApproveStudentMutationRequest $request, StudentMutation $student_mutation)
    {
        try {
            $user = $request->user();
            $action = $request->validated('action');
            $notes = $request->validated('notes');

            if ($action === 'approve') {
                $mutation = $this->mutationService->approve($student_mutation, $user->id, $notes);
                $message = 'Permohonan mutasi disetujui. Data siswa telah dipindahkan ke sekolah tujuan.';
            } else {
                $mutation = $this->mutationService->reject(
                    $student_mutation,
                    $user->id,
                    $request->validated('rejection_reason'),
                    $notes
                );
                $message = 'Permohonan mutasi ditolak.';
            }

            return response()->json([
                'message' => $message,
                'data' => new StudentMutationResource($mutation),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Student mutation approve/reject failed', [
                'id' => $student_mutation->id,
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
    public function cancel(CancelStudentMutationRequest $request, StudentMutation $student_mutation)
    {
        try {
            $mutation = $this->mutationService->requestCancel(
                $student_mutation,
                $request->user(),
                $request->validated('reason')
            );

            $message = $mutation->status === 'cancel_pending'
                ? 'Permohonan pembatalan dikirim. Menunggu persetujuan admin sekolah tujuan.'
                : 'Permohonan mutasi berhasil dibatalkan.';

            return response()->json([
                'message' => $message,
                'data' => new StudentMutationResource($mutation),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Student mutation cancel failed', [
                'id' => $student_mutation->id,
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
    public function decideCancel(DecideCancelStudentMutationRequest $request, StudentMutation $student_mutation)
    {
        try {
            $user = $request->user();
            $action = $request->validated('action');

            if ($action === 'approve') {
                $mutation = $this->mutationService->approveCancel(
                    $student_mutation,
                    $user,
                    $request->validated('notes')
                );
                $message = 'Pembatalan mutasi disetujui. Data siswa dikembalikan ke sekolah asal.';
            } else {
                $mutation = $this->mutationService->rejectCancel(
                    $student_mutation,
                    $user,
                    $request->validated('rejection_reason')
                );
                $message = 'Permohonan pembatalan mutasi ditolak. Mutasi tetap berlaku.';
            }

            return response()->json([
                'message' => $message,
                'data' => new StudentMutationResource($mutation),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Student mutation decide-cancel failed', [
                'id' => $student_mutation->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'message' => 'Gagal memproses pembatalan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Lookup student by NIK at current institution (preview before submitting mutation out).
     */
    public function lookupStudent(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $nik = trim((string) $request->get('nik', ''));
            if ($nik === '' || !preg_match('/^[0-9]{16}$/', $nik)) {
                return response()->json(['message' => 'NIK wajib diisi (16 digit angka).'], 422);
            }

            $student = $this->mutationService->lookupStudentByNik($institutionId, $nik);

            return response()->json(['data' => $student]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Student mutation lookup failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencari data siswa.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Lookup student at origin school by NPSN + NIK (preview before pull).
     */
    public function lookupStudentAtOrigin(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $originNpsn = trim((string) $request->get('origin_npsn', ''));
            $nik = trim((string) $request->get('nik', ''));
            if (strlen($originNpsn) !== 8) {
                return response()->json(['message' => 'NPSN sekolah asal harus 8 digit.'], 422);
            }
            if ($nik === '' || !preg_match('/^[0-9]{16}$/', $nik)) {
                return response()->json(['message' => 'NIK wajib diisi (16 digit angka).'], 422);
            }

            $student = $this->mutationService->lookupStudentAtOriginByNpsn($originNpsn, $nik, $institutionId);

            return response()->json(['data' => $student]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        } catch (\Exception $e) {
            Log::error('Student mutation lookup at origin failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mencari data siswa di sekolah asal.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get institutions by NPSN search (for dropdown/autocomplete, same jenjang only).
     */
    public function searchTargetInstitutions(Request $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $origin = Institution::find($institutionId);
        if (!$origin) {
            return response()->json(['message' => 'Sekolah asal tidak ditemukan.'], 404);
        }

        $search = $request->get('q', '');
        $query = Institution::where('id', '!=', $institutionId)
            ->where('is_active', true)
            ->where(function ($q) use ($origin) {
                $group = Institution::getMutasiLevelGroup($origin->level);
                if ($group) {
                    $levels = match ($group) {
                        'dasar' => ['SD', 'MI'],
                        'menengah' => ['SMP', 'MTs'],
                        'atas' => ['SMA', 'MA', 'SMK', 'MAK'],
                        'paud' => ['PAUD', 'TK'],
                        default => [],
                    };
                    $q->whereIn('level', $levels);
                }
            });

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
     * Get institutions by NPSN search for "origin" (sekolah asal) when target admin tarik siswa.
     * Same jenjang as user's institution; exclude self.
     */
    public function searchOriginInstitutions(Request $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $target = Institution::find($institutionId);
        if (!$target) {
            return response()->json(['message' => 'Sekolah Anda tidak ditemukan.'], 404);
        }

        $search = $request->get('q', '');
        $query = Institution::where('id', '!=', $institutionId)
            ->where('is_active', true)
            ->where(function ($q) use ($target) {
                $group = Institution::getMutasiLevelGroup($target->level);
                if ($group) {
                    $levels = match ($group) {
                        'dasar' => ['SD', 'MI'],
                        'menengah' => ['SMP', 'MTs'],
                        'atas' => ['SMA', 'MA', 'SMK', 'MAK'],
                        'paud' => ['PAUD', 'TK'],
                        default => [],
                    };
                    $q->whereIn('level', $levels);
                }
            });

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
     * Laporan mutasi: keluar/masuk per periode untuk institusi user.
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
                'data' => StudentMutationResource::collection($result['data']),
                'meta' => [
                    'current_page' => $result['data']->currentPage(),
                    'last_page' => $result['data']->lastPage(),
                    'per_page' => $result['data']->perPage(),
                    'total' => $result['data']->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Student mutation report failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil laporan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export Buku Mutasi: PDF atau CSV (filter from, to, type sama seperti report).
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
                $filename = 'Buku_Mutasi_' . date('Y-m-d_His') . '.csv';
                return response()->streamDownload(function () use ($mutations, $institutionId) {
                    $out = fopen('php://output', 'w');
                    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                    fputcsv($out, [
                        'No', 'Tanggal', 'NIK', 'NISN', 'Nama Siswa', 'JK', 'Kelas', 'Jenis', 'Sekolah Asal', 'NPSN Asal',
                        'Sekolah Tujuan', 'NPSN Tujuan', 'Alasan/Keterangan', 'Disetujui oleh',
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
                            $m->student?->nik ?? '',
                            $m->student?->nisn ?? '',
                            $m->student?->name ?? '',
                            $m->student_gender ?? $m->student?->gender ?? '',
                            $m->student_grade ?? '',
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
            $asOfDate = $to ?: ($from ?: now()->toDateString());
            $data = [
                'institution' => $institution,
                'institution_id' => $institutionId,
                'mutations' => $mutations,
                'date_from' => $from,
                'date_to' => $to,
                'printed_at' => $printedAt,
                'as_of_date' => $asOfDate,
            ];
            $pdf = DomPDF::loadView('buku_mutasi.print', $data);
            $pdfFilename = 'Buku_Mutasi_' . date('Y-m-d_His') . '.pdf';
            // Inline stream agar frontend bisa preview di tab baru (bukan force-download).
            return $pdf->stream($pdfFilename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('Student mutation export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor Buku Mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Riwayat mutasi per siswa (hanya jika institusi user terlibat).
     */
    public function historyByStudent(Request $request, int $student_id)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $list = $this->mutationService->historyForStudent($student_id, $institutionId);

            return StudentMutationResource::collection($list);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Student mutation history failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Riwayat mutasi per siswa by NIK.
     */
    public function historyByNik(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $nik = $request->get('nik');
            if (!$nik || !is_string($nik) || !preg_match('/^[0-9]{16}$/', trim($nik))) {
                return response()->json(['message' => 'NIK wajib diisi (16 digit angka).'], 422);
            }
            $list = $this->mutationService->historyByNik(trim($nik), $institutionId);
            return StudentMutationResource::collection($list);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Student mutation history by NIK failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
