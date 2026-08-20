<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePpdbApplicantRequest;
use App\Http\Requests\UpdatePpdbApplicantRequest;
use App\Http\Resources\PpdbApplicantResource;
use App\Models\AuditLog;
use App\Models\PpdbApplicant;
use App\Exports\PpdbApplicantsExport;
use App\Models\PpdbApplicantDocument;
use App\Models\PpdbPeriod;
use App\Models\StudentDocument;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Helpers\FileUploadRules;
use App\Services\StudentService;
use App\Services\StudentAccountService;
use App\Notifications\PpdbApplicantMailNotification;
use App\Support\PpdbDocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelManager;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PpdbApplicantController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
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

            $query = PpdbApplicant::query()
                ->with(['period:id,name,open_date,close_date,status,institution_id', 'channel:id,code,name'])
                ->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId));

            if ($request->filled('ppdb_period_id')) {
                $query->where('ppdb_period_id', $request->ppdb_period_id);
            }
            if ($request->filled('ppdb_channel_id')) {
                $query->where('ppdb_channel_id', $request->ppdb_channel_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } elseif ($request->filled('needs_verification') && $request->boolean('needs_verification')) {
                $query->whereIn('status', ['submitted', 'verification']);
            } elseif ($request->filled('needs_result') && $request->boolean('needs_result')) {
                $query->where('status', 'verified');
            }
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }
            if ($request->filled('search')) {
                $term = '%' . $request->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('registration_number', 'like', $term)
                        ->orWhere('nisn', 'like', $term)
                        ->orWhere('nik', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            }

            $query->orderByDesc('created_at');
            $perPage = min($request->get('per_page', 15), 100);
            $applicants = $query->paginate($perPage);

            return PpdbApplicantResource::collection($applicants);
        } catch (\Exception $e) {
            Log::error('PpdbApplicant index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data calon peserta didik.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StorePpdbApplicantRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $period = PpdbPeriod::findOrFail($request->ppdb_period_id);
            if ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $registrationNumber = $this->generateRegistrationNumber($period->id);

            $data = $request->validated();
            $data['registration_number'] = $registrationNumber;
            $data['status'] = 'draft';

            $applicant = PpdbApplicant::create($data);
            $applicant->load(['period', 'channel']);
            return (new PpdbApplicantResource($applicant))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('PpdbApplicant store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menambahkan calon peserta didik.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, PpdbApplicant $ppdb_applicant): PpdbApplicantResource|JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $ppdb_applicant->load(['period.academicYear', 'channel', 'documents']);
        return new PpdbApplicantResource($ppdb_applicant);
    }

    public function update(UpdatePpdbApplicantRequest $request, PpdbApplicant $ppdb_applicant): PpdbApplicantResource|JsonResponse
    {
        try {
            $user = $request->user();
            $period = $ppdb_applicant->period;
            if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            $ppdb_applicant->update($data);
            $ppdb_applicant->load(['period', 'channel', 'documents']);
            return new PpdbApplicantResource($ppdb_applicant);
        } catch (\Exception $e) {
            Log::error('PpdbApplicant update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memperbarui calon peserta didik.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, PpdbApplicant $ppdb_applicant): JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        foreach ($ppdb_applicant->documents as $doc) {
            PpdbDocumentStorage::delete($doc->file_path);
        }
        $ppdb_applicant->delete();
        return response()->json(['message' => 'Calon peserta didik berhasil dihapus.']);
    }

    public function setVerification(Request $request, PpdbApplicant $ppdb_applicant): PpdbApplicantResource|JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'documents_verified' => 'required|boolean',
            'verification_notes' => 'nullable|string',
        ]);

        $oldVerified = $ppdb_applicant->documents_verified;
        $oldStatus = $ppdb_applicant->status;
        $ppdb_applicant->update([
            'documents_verified' => $request->documents_verified,
            'verification_notes' => $request->verification_notes,
            'status' => $request->documents_verified ? 'verified' : 'verification',
        ]);
        AuditLog::logManual($request, 'ppdb_applicant_verification', PpdbApplicant::class, $ppdb_applicant->id, [
            'documents_verified' => $oldVerified,
            'status' => $oldStatus,
        ], [
            'documents_verified' => $request->documents_verified,
            'status' => $ppdb_applicant->status,
        ], $ppdb_applicant->period?->institution_id);
        $ppdb_applicant->load(['period', 'channel', 'documents']);
        return new PpdbApplicantResource($ppdb_applicant);
    }

    /**
     * Bulk set verification untuk banyak calon sekaligus (tandai verified).
     */
    public function bulkVerification(Request $request): JsonResponse
    {
        $user = $request->user();
        $institutionId = $user->institution_id;
        if ($user->isSuperAdmin() && $request->filled('institution_id')) {
            $institutionId = (int) $request->institution_id;
        }
        if (!$institutionId && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $request->validate([
            'applicant_ids' => 'required|array',
            'applicant_ids.*' => 'integer|exists:ppdb_applicants,id',
            'documents_verified' => 'required|boolean',
            'verification_notes' => 'nullable|string|max:1000',
        ]);

        $ids = array_unique(array_map('intval', $request->applicant_ids));
        $applicants = PpdbApplicant::query()
            ->with('period:id,institution_id')
            ->whereIn('id', $ids)
            ->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        $updated = 0;
        foreach ($applicants as $applicant) {
            $oldStatus = $applicant->status;
            $applicant->update([
                'documents_verified' => $request->documents_verified,
                'verification_notes' => $request->verification_notes,
                'status' => $request->documents_verified ? 'verified' : 'verification',
            ]);
            AuditLog::logManual($request, 'ppdb_applicant_bulk_verification', PpdbApplicant::class, $applicant->id, [
                'status' => $oldStatus,
            ], [
                'documents_verified' => $request->documents_verified,
                'status' => $applicant->status,
            ], $institutionId);
            $updated++;
        }

        return response()->json([
            'message' => $updated . ' calon berhasil diperbarui.',
            'updated_count' => $updated,
        ]);
    }

    /**
     * Bulk set hasil seleksi (passed / reserve / failed).
     */
    public function bulkResult(Request $request): JsonResponse
    {
        $user = $request->user();
        $institutionId = $user->institution_id;
        if ($user->isSuperAdmin() && $request->filled('institution_id')) {
            $institutionId = (int) $request->institution_id;
        }
        if (!$institutionId && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $request->validate([
            'applicant_ids' => 'required|array|min:1',
            'applicant_ids.*' => 'integer|exists:ppdb_applicants,id',
            'status' => 'required|in:passed,reserve,failed',
            'result_notes' => 'nullable|string|max:1000',
        ]);

        $ids = array_unique(array_map('intval', $request->applicant_ids));
        $applicants = PpdbApplicant::query()
            ->with(['period', 'channel'])
            ->whereIn('id', $ids)
            ->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId))
            ->get();

        if ($applicants->isEmpty()) {
            return response()->json(['message' => 'Tidak ada calon yang valid.'], 422);
        }

        $status = $request->status;
        $updated = 0;
        $skipped = [];

        if ($status === 'passed') {
            $byChannel = $applicants->groupBy('ppdb_channel_id');
            foreach ($byChannel as $channelId => $group) {
                $channel = $group->first()->channel;
                if (!$channel || $channel->quota === null || (int) $channel->quota <= 0) {
                    continue;
                }
                $periodId = $group->first()->ppdb_period_id;
                $alreadyPassed = PpdbApplicant::query()
                    ->where('ppdb_period_id', $periodId)
                    ->where('ppdb_channel_id', $channelId)
                    ->where('status', 'passed')
                    ->whereNotIn('id', $group->pluck('id'))
                    ->count();
                $newPassers = $group->filter(fn ($a) => $a->status !== 'passed')->count();
                if ($alreadyPassed + $newPassers > (int) $channel->quota) {
                    return response()->json([
                        'message' => 'Kuota jalur "' . $channel->name . '" tidak cukup untuk ' . $newPassers . ' calon (kuota ' . (int) $channel->quota . ', sudah terisi ' . $alreadyPassed . ').',
                    ], 422);
                }
            }
        }

        foreach ($applicants as $applicant) {
            if (!in_array($applicant->status, ['verified', 'submitted', 'verification', 'passed', 'reserve', 'failed'], true)) {
                $skipped[] = $applicant->registration_number;
                continue;
            }

            $oldStatus = $applicant->status;
            $period = $applicant->period;
            $updates = [
                'status' => $status,
                'result_notes' => $request->result_notes,
                'announcement_at' => now(),
            ];
            if (in_array($status, ['passed', 'reserve'], true) && $period?->re_registration_deadline) {
                $updates['re_registration_deadline'] = $period->re_registration_deadline;
            }

            $applicant->update($updates);
            AuditLog::logManual($request, 'ppdb_applicant_bulk_result', PpdbApplicant::class, $applicant->id, [
                'status' => $oldStatus,
            ], [
                'status' => $status,
            ], $institutionId);
            $updated++;
        }

        return response()->json([
            'message' => $updated . ' calon berhasil diperbarui.',
            'updated_count' => $updated,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Update status pembayaran calon.
     */
    public function setPayment(Request $request, PpdbApplicant $ppdb_applicant): PpdbApplicantResource|JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'payment_status' => 'required|in:unpaid,pending,paid,waived',
            'payment_type' => 'nullable|in:registration,re_registration',
            'payment_amount' => 'nullable|numeric|min:0',
            'payment_notes' => 'nullable|string|max:1000',
            'paid_at' => 'nullable|date',
        ]);

        $paymentType = $data['payment_type'] ?? (
            in_array($ppdb_applicant->status, ['passed', 'reserve', 're_registration'], true)
                ? 're_registration'
                : 'registration'
        );

        $amount = $data['payment_amount'] ?? null;
        if ($amount === null) {
            $amount = $paymentType === 're_registration'
                ? $period->re_registration_fee
                : $period->registration_fee;
        }

        $old = [
            'payment_status' => $ppdb_applicant->payment_status,
            'payment_amount' => $ppdb_applicant->payment_amount,
        ];

        $updates = [
            'payment_status' => $data['payment_status'],
            'payment_type' => $paymentType,
            'payment_amount' => $amount,
            'payment_notes' => $data['payment_notes'] ?? $ppdb_applicant->payment_notes,
        ];

        if ($data['payment_status'] === 'paid') {
            $updates['paid_at'] = isset($data['paid_at']) ? $data['paid_at'] : ($ppdb_applicant->paid_at ?? now());
        } elseif (in_array($data['payment_status'], ['unpaid', 'waived', 'pending'], true)) {
            // Clear paid_at for non-paid statuses (waived/pending must not keep a prior paid timestamp).
            $updates['paid_at'] = null;
        } elseif (isset($data['paid_at'])) {
            $updates['paid_at'] = $data['paid_at'];
        }

        $ppdb_applicant->update($updates);
        AuditLog::logManual($request, 'ppdb_applicant_payment', PpdbApplicant::class, $ppdb_applicant->id, $old, [
            'payment_status' => $updates['payment_status'],
            'payment_amount' => $updates['payment_amount'],
            'payment_type' => $updates['payment_type'],
        ], $period->institution_id);

        $ppdb_applicant->load(['period', 'channel', 'documents']);
        return new PpdbApplicantResource($ppdb_applicant);
    }

    public function submit(Request $request, PpdbApplicant $ppdb_applicant): PpdbApplicantResource|JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ppdb_applicant->status !== 'draft') {
            return response()->json([
                'message' => 'Hanya data draft yang dapat disubmit.',
            ], 422);
        }

        $ppdb_applicant->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $ppdb_applicant->load(['period', 'channel', 'documents']);
        return new PpdbApplicantResource($ppdb_applicant);
    }

    /**
     * Export daftar calon PPDB ke CSV.
     */
    public function export(Request $request): StreamedResponse|JsonResponse
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

            $query = PpdbApplicant::query()
                ->with(['period:id,name,open_date,close_date,status,institution_id', 'channel:id,code,name'])
                ->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId));

            if ($request->filled('ppdb_period_id')) {
                $query->where('ppdb_period_id', $request->ppdb_period_id);
            }
            if ($request->filled('ppdb_channel_id')) {
                $query->where('ppdb_channel_id', $request->ppdb_channel_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } elseif ($request->filled('needs_verification') && $request->boolean('needs_verification')) {
                $query->whereIn('status', ['submitted', 'verification']);
            } elseif ($request->filled('needs_result') && $request->boolean('needs_result')) {
                $query->where('status', 'verified');
            }
            if ($request->filled('payment_status')) {
                $query->where('payment_status', $request->payment_status);
            }
            if ($request->filled('search')) {
                $term = '%' . $request->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('registration_number', 'like', $term)
                        ->orWhere('nisn', 'like', $term)
                        ->orWhere('nik', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            }

            $applicants = $query->orderBy('registration_number')->limit(5000)->get();
            $periodName = $applicants->first()?->period?->name ?? 'ppdb';
            $format = strtolower((string) $request->get('format', 'csv'));
            $requestedColumns = $request->filled('columns')
                ? array_map('trim', explode(',', (string) $request->columns))
                : null;

            $allColumns = [
                'registration_number' => 'No. Pendaftaran',
                'name' => 'Nama',
                'nik' => 'NIK',
                'nisn' => 'NISN',
                'gender' => 'Jenis Kelamin',
                'birth_place' => 'Tempat Lahir',
                'birth_date' => 'Tanggal Lahir',
                'address' => 'Alamat',
                'phone' => 'Telepon',
                'email' => 'Email',
                'previous_school' => 'Asal Sekolah',
                'previous_school_npsn' => 'NPSN Asal',
                'previous_school_address' => 'Alamat Asal',
                'channel' => 'Jalur',
                'period' => 'Periode',
                'status' => 'Status',
                'rank' => 'Rank',
                'father_name' => 'Ayah',
                'mother_name' => 'Ibu',
                'guardian_name' => 'Wali',
                'created_at' => 'Tgl Daftar',
                'documents_verified' => 'Berkas Verifikasi',
                'payment_status' => 'Status Bayar',
                'payment_amount' => 'Nominal Bayar',
                'notes' => 'Catatan',
            ];
            $cols = $requestedColumns
                ? array_intersect_key($allColumns, array_flip(array_filter($requestedColumns)))
                : $allColumns;
            $colKeys = array_keys($cols);
            $headers = array_values($cols);

            if ($format === 'xlsx') {
                return $this->exportExcel($applicants, $colKeys, $headers, $periodName);
            }

            $filename = 'calon-ppdb-' . Str::slug($periodName) . '-' . date('Y-m-d-His') . '.csv';
            return response()->streamDownload(function () use ($applicants, $colKeys, $headers) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, $headers);
                foreach ($applicants as $a) {
                    $row = $this->exportRow($a, $colKeys);
                    fputcsv($out, $row);
                }
                fclose($out);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Exception $e) {
            Log::error('PpdbApplicant export failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengekspor data calon.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Set hasil seleksi (lulus / cadangan / tidak lulus) dan optional rank, catatan.
     */
    public function setResult(Request $request, PpdbApplicant $ppdb_applicant): PpdbApplicantResource|JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:passed,reserve,failed',
            'rank' => 'nullable|integer|min:1',
            'result_notes' => 'nullable|string',
        ]);

        if ($request->status === 'passed') {
            $channel = $ppdb_applicant->channel;
            if ($channel && $channel->quota !== null && (int) $channel->quota > 0) {
                $currentPassedCount = PpdbApplicant::query()
                    ->where('ppdb_period_id', $period->id)
                    ->where('ppdb_channel_id', $channel->id)
                    ->where('status', 'passed')
                    ->where('id', '!=', $ppdb_applicant->id)
                    ->count();
                $isNewlyPassed = $ppdb_applicant->status !== 'passed';
                $totalPassed = $currentPassedCount + ($isNewlyPassed ? 1 : 0);
                if ($totalPassed > (int) $channel->quota) {
                    return response()->json([
                        'message' => 'Kuota jalur "' . $channel->name . '" sudah terpenuhi (' . (int) $channel->quota . '). Tidak dapat menambah calon lulus.',
                    ], 422);
                }
            }
        }

        $updates = [
            'status' => $request->status,
            'rank' => $request->rank,
            'result_notes' => $request->result_notes,
        ];
        if ($request->status === 'passed' || $request->status === 'reserve') {
            $updates['announcement_at'] = $request->date('announcement_at') ?? now();
            if ($period->re_registration_deadline) {
                $updates['re_registration_deadline'] = $period->re_registration_deadline;
            }
        } else {
            $updates['announcement_at'] = $request->date('announcement_at') ?? now();
        }

        $oldStatus = $ppdb_applicant->status;
        $oldRank = $ppdb_applicant->rank;
        $ppdb_applicant->update($updates);
        AuditLog::logManual($request, 'ppdb_applicant_result', PpdbApplicant::class, $ppdb_applicant->id, [
            'status' => $oldStatus,
            'rank' => $oldRank,
        ], [
            'status' => $updates['status'],
            'rank' => $updates['rank'] ?? null,
        ], $period->institution_id);
        $ppdb_applicant->load(['period.institution', 'channel', 'documents']);

        $email = trim((string) ($ppdb_applicant->email ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::route('mail', $email)
                ->notify(new PpdbApplicantMailNotification($ppdb_applicant, 'result'));
        }

        return new PpdbApplicantResource($ppdb_applicant);
    }

    /**
     * Konfirmasi daftar ulang (calon lulus/cadangan menyatakan akan daftar ulang).
     */
    public function confirmReRegistration(Request $request, PpdbApplicant $ppdb_applicant): PpdbApplicantResource|JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($ppdb_applicant->status, ['passed', 'reserve'], true)) {
            return response()->json([
                'message' => 'Hanya calon yang lulus atau cadangan yang dapat konfirmasi daftar ulang.',
            ], 422);
        }

        $ppdb_applicant->update([
            'status' => 're_registration',
            're_registration_confirmed_at' => now(),
        ]);
        $ppdb_applicant->load(['period', 'channel', 'documents']);
        return new PpdbApplicantResource($ppdb_applicant);
    }

    /**
     * Jadikan siswa: buat record Student dari data calon dan tautkan.
     */
    public function convertToStudent(Request $request, PpdbApplicant $ppdb_applicant): JsonResponse
    {
        $user = $request->user();
        $period = $ppdb_applicant->period;
        if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($ppdb_applicant->student_id) {
            return response()->json([
                'message' => 'Calon ini sudah dijadikan siswa.',
                'data' => ['student_id' => $ppdb_applicant->student_id],
            ], 422);
        }

        if ($ppdb_applicant->status !== 're_registration' && !$ppdb_applicant->re_registration_confirmed_at) {
            return response()->json([
                'message' => 'Calon harus sudah konfirmasi daftar ulang sebelum dijadikan siswa.',
            ], 422);
        }

        $request->validate([
            'class_id' => 'nullable|exists:class,id',
        ]);

        $period->load('academicYear');
        $institutionId = $period->institution_id;
        $academicYearId = $period->academic_year_id;
        $semester = Semester::where('academic_year_id', $academicYearId)->orderBy('order')->first();
        $semesterId = $semester?->id;

        $academicYear = $period->academicYear;
        $classId = $request->class_id ? (int) $request->class_id : null;
        $classModel = $classId ? SchoolClass::find($classId) : null;
        $studentData = [
            'institution_id' => $institutionId,
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'class_id' => $classId,
            'tingkat' => $classModel?->grade,
            'class' => $classModel?->name,
            'academic_year' => $academicYear->code ?? $academicYear->name ?? null,
            'nis' => null,
            'nisn' => $ppdb_applicant->nisn,
            'nik' => $ppdb_applicant->nik,
            'name' => $ppdb_applicant->name,
            'gender' => $ppdb_applicant->gender,
            'birth_date' => $ppdb_applicant->birth_date,
            'birth_place' => $ppdb_applicant->birth_place,
            'address' => $ppdb_applicant->address,
            'phone' => $ppdb_applicant->phone,
            'email' => $ppdb_applicant->email,
            'religion' => $ppdb_applicant->religion,
            'previous_school' => $ppdb_applicant->previous_school,
            'previous_school_npsn' => $ppdb_applicant->previous_school_npsn,
            'previous_school_address' => $ppdb_applicant->previous_school_address,
            'father_name' => $ppdb_applicant->father_name,
            'mother_name' => $ppdb_applicant->mother_name,
            'guardian_name' => $ppdb_applicant->guardian_name,
            'guardian_phone' => $ppdb_applicant->guardian_phone,
            'status' => 'Aktif',
        ];

        $studentService = app(StudentService::class);
        $student = $studentService->create($studentData);
        $accountService = app(StudentAccountService::class);
        $account = $accountService->findAccount($student);

        foreach ($ppdb_applicant->documents as $doc) {
            $destPath = 'student_documents/' . $student->id . '/' . basename($doc->file_path);
            if (PpdbDocumentStorage::copyToStudentDocuments($doc->file_path, $destPath)) {
                StudentDocument::create([
                    'student_id' => $student->id,
                    'name' => $doc->name,
                    'file_path' => $destPath,
                    'file_name' => $doc->file_name,
                    'file_size' => $doc->file_size,
                    'mime_type' => $doc->mime_type,
                    'description' => 'Dari PPDB: ' . ($doc->description ?? ''),
                ]);
            }
        }

        $ppdb_applicant->update([
            'student_id' => $student->id,
            'status' => 'converted',
        ]);

        AuditLog::logManual($request, 'ppdb_applicant_convert_to_student', PpdbApplicant::class, $ppdb_applicant->id, [
            'student_id' => null,
            'status' => 're_registration',
        ], [
            'student_id' => $student->id,
            'status' => 'converted',
        ], $institutionId);

        return response()->json([
            'message' => 'Calon berhasil dijadikan siswa.',
            'data' => [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'applicant' => new PpdbApplicantResource($ppdb_applicant->fresh(['period', 'channel', 'student'])),
            ],
            'login_hint' => $account ? $accountService->loginHintFor($student) : null,
        ]);
    }

    public function uploadDocument(Request $request, PpdbApplicant $ppdb_applicant): JsonResponse
    {
        try {
            $user = $request->user();
            $period = $ppdb_applicant->period;
            if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $documentCount = $ppdb_applicant->documents()->count();
            if ($documentCount >= 20) {
                return response()->json([
                    'message' => 'Maksimal 20 file dokumen per calon peserta didik',
                ], 400);
            }

            $rules = array_merge(
                FileUploadRules::studentDocument(),
                [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                ]
            );
            $messages = FileUploadRules::messages(
                FileUploadRules::TYPE_MIXED,
                FileUploadRules::SIZE_SMALL,
                'file',
                false
            );
            $request->validate($rules, $messages);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $fileName = time() . '_' . $safeName . '.' . $extension;
            $filePath = PpdbDocumentStorage::store($file, (int) $ppdb_applicant->id, $fileName);

            $document = $ppdb_applicant->documents()->create([
                'name' => $request->name,
                'file_path' => $filePath,
                'file_name' => $originalName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $request->description,
            ]);

            return response()->json([
                'message' => 'Dokumen berhasil diunggah.',
                'data' => [
                    'id' => $document->id,
                    'name' => $document->name,
                    'file_name' => $document->file_name,
                    'file_size' => $document->file_size,
                    'mime_type' => $document->mime_type,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PpdbApplicant uploadDocument failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengunggah dokumen.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deleteDocument(Request $request, PpdbApplicant $ppdb_applicant, int $documentId): JsonResponse
    {
        try {
            $user = $request->user();
            $period = $ppdb_applicant->period;
            if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $document = PpdbApplicantDocument::where('ppdb_applicant_id', $ppdb_applicant->id)->findOrFail($documentId);
            PpdbDocumentStorage::delete($document->file_path);
            $document->delete();
            return response()->json(['message' => 'Dokumen berhasil dihapus.']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Dokumen tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('PpdbApplicant deleteDocument failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menghapus dokumen.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function downloadDocument(Request $request, PpdbApplicant $ppdb_applicant, int $documentId): JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        try {
            $user = $request->user();
            $period = $ppdb_applicant->period;
            if (!$period || ($user->institution_id !== $period->institution_id && !$user->isSuperAdmin())) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $document = PpdbApplicantDocument::where('ppdb_applicant_id', $ppdb_applicant->id)->findOrFail($documentId);
            if (!PpdbDocumentStorage::exists($document->file_path)) {
                return response()->json(['message' => 'File dokumen tidak ditemukan.'], 404);
            }
            return PpdbDocumentStorage::download($document->file_path, $document->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Dokumen tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('PpdbApplicant downloadDocument failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengunduh dokumen.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Satu baris data export untuk satu calon (urutan sesuai colKeys).
     */
    private function exportRow(PpdbApplicant $a, array $colKeys): array
    {
        $map = [
            'registration_number' => $a->registration_number ?? '',
            'name' => $a->name ?? '',
            'nik' => $a->nik ?? '',
            'nisn' => $a->nisn ?? '',
            'gender' => $a->gender === 'L' ? 'Laki-laki' : ($a->gender === 'P' ? 'Perempuan' : ''),
            'birth_place' => $a->birth_place ?? '',
            'birth_date' => $a->birth_date?->format('Y-m-d') ?? '',
            'address' => $a->address ?? '',
            'phone' => $a->phone ?? '',
            'email' => $a->email ?? '',
            'previous_school' => $a->previous_school ?? '',
            'previous_school_npsn' => $a->previous_school_npsn ?? '',
            'previous_school_address' => $a->previous_school_address ?? '',
            'channel' => $a->channel?->name ?? '',
            'period' => $a->period?->name ?? '',
            'status' => $a->status ?? '',
            'rank' => $a->rank ?? '',
            'father_name' => $a->father_name ?? '',
            'mother_name' => $a->mother_name ?? '',
            'guardian_name' => $a->guardian_name ?? '',
            'created_at' => $a->created_at?->format('Y-m-d H:i') ?? '',
            'documents_verified' => $a->documents_verified ? 'Ya' : 'Tidak',
            'payment_status' => $a->payment_status ?? 'unpaid',
            'payment_amount' => $a->payment_amount ?? '',
            'notes' => $a->notes ?? '',
        ];
        $row = [];
        foreach ($colKeys as $key) {
            $row[] = $map[$key] ?? '';
        }
        return $row;
    }

    /**
     * Export ke Excel (xlsx) dengan Laravel Excel 3.x.
     */
    private function exportExcel($applicants, array $colKeys, array $headers, string $periodName)
    {
        $filename = 'calon-ppdb-' . Str::slug($periodName) . '-' . date('Y-m-d-His') . '.xlsx';
        $export = new PpdbApplicantsExport($applicants, $colKeys, $headers);
        return app(ExcelManager::class)->download($export, $filename, ExcelManager::XLSX);
    }

    private function generateRegistrationNumber(int $periodId): string
    {
        $last = PpdbApplicant::where('ppdb_period_id', $periodId)
            ->where('registration_number', 'like', 'PPDB-' . $periodId . '-%')
            ->orderByDesc('id')
            ->value('registration_number');

        $seq = 1;
        if ($last && preg_match('/PPDB-\d+-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return 'PPDB-' . $periodId . '-' . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
