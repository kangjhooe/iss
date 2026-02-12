<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePpdbApplicantRequest;
use App\Http\Requests\UpdatePpdbApplicantRequest;
use App\Http\Resources\PpdbApplicantResource;
use App\Models\PpdbApplicant;
use App\Models\PpdbApplicantDocument;
use App\Models\PpdbPeriod;
use App\Models\Student;
use App\Models\StudentDocument;
use App\Models\Semester;
use App\Models\SchoolClass;
use App\Helpers\FileUploadRules;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            if (Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
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

        $ppdb_applicant->update([
            'documents_verified' => $request->documents_verified,
            'verification_notes' => $request->verification_notes,
            'status' => $request->documents_verified ? 'verified' : 'verification',
        ]);
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
            $filename = 'calon-ppdb-' . Str::slug($periodName) . '-' . date('Y-m-d-His') . '.csv';

            return response()->streamDownload(function () use ($applicants) {
                $out = fopen('php://output', 'w');
                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
                fputcsv($out, [
                    'No. Pendaftaran', 'Nama', 'NIK', 'NISN', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir',
                    'Alamat', 'Telepon', 'Email', 'Asal Sekolah', 'NPSN Asal', 'Alamat Asal', 'Jalur', 'Periode', 'Status', 'Rank',
                    'Ayah', 'Ibu', 'Wali', 'Tgl Daftar', 'Berkas Verifikasi', 'Catatan',
                ]);
                foreach ($applicants as $a) {
                    fputcsv($out, [
                        $a->registration_number ?? '',
                        $a->name ?? '',
                        $a->nik ?? '',
                        $a->nisn ?? '',
                        $a->gender === 'L' ? 'Laki-laki' : ($a->gender === 'P' ? 'Perempuan' : ''),
                        $a->birth_place ?? '',
                        $a->birth_date?->format('Y-m-d') ?? '',
                        $a->address ?? '',
                        $a->phone ?? '',
                        $a->email ?? '',
                        $a->previous_school ?? '',
                        $a->previous_school_npsn ?? '',
                        $a->previous_school_address ?? '',
                        $a->channel?->name ?? '',
                        $a->period?->name ?? '',
                        $a->status ?? '',
                        $a->rank ?? '',
                        $a->father_name ?? '',
                        $a->mother_name ?? '',
                        $a->guardian_name ?? '',
                        $a->created_at?->format('Y-m-d H:i') ?? '',
                        $a->documents_verified ? 'Ya' : 'Tidak',
                        $a->notes ?? '',
                    ]);
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

        $ppdb_applicant->update($updates);
        $ppdb_applicant->load(['period', 'channel', 'documents']);
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
        $yearCode = $academicYear ? preg_replace('/[^0-9]/', '', (string) $academicYear->code) : date('Y');
        $yearCode = substr($yearCode, 0, 4) ?: date('Y');
        $nis = $this->generateNis($institutionId, (int) $yearCode);

        $classId = $request->class_id ? (int) $request->class_id : null;
        $classModel = $classId ? SchoolClass::find($classId) : null;
        $studentData = [
            'institution_id' => $institutionId,
            'academic_year_id' => $academicYearId,
            'semester_id' => $semesterId,
            'class_id' => $classId,
            'class' => $classModel?->name,
            'academic_year' => $academicYear->name ?? $academicYear->code ?? null,
            'nis' => $nis,
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

        foreach ($ppdb_applicant->documents as $doc) {
            $destPath = 'student_documents/' . $student->id . '/' . basename($doc->file_path);
            if (Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->copy($doc->file_path, $destPath);
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

        return response()->json([
            'message' => 'Calon berhasil dijadikan siswa.',
            'data' => [
                'student_id' => $student->id,
                'nis' => $student->nis,
                'applicant' => new PpdbApplicantResource($ppdb_applicant->fresh(['period', 'channel', 'student'])),
            ],
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
            $filePath = $file->storeAs('ppdb_applicant_documents/' . $ppdb_applicant->id, $fileName, 'public');

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
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }
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
            if (!Storage::disk('public')->exists($document->file_path)) {
                return response()->json(['message' => 'File dokumen tidak ditemukan.'], 404);
            }
            return Storage::disk('public')->download($document->file_path, $document->file_name);
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

    private function generateNis(int $institutionId, int $yearCode): string
    {
        $prefix = (string) $yearCode;
        $nisList = Student::where('institution_id', $institutionId)
            ->where('nis', 'like', $prefix . '%')
            ->pluck('nis');

        $seq = 1;
        foreach ($nisList as $nis) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $nis, $m)) {
                $n = (int) $m[1];
                if ($n >= $seq) {
                    $seq = $n + 1;
                }
            }
        }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }
}
