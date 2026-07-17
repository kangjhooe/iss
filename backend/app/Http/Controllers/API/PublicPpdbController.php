<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\FileUploadRules;
use App\Http\Requests\PublicPpdbRegisterRequest;
use App\Models\Institution;
use App\Models\PpdbApplicant;
use App\Models\PpdbApplicantDocument;
use App\Models\PpdbChannel;
use App\Models\PpdbPeriod;
use App\Models\Student;
use App\Notifications\PpdbRegistrationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublicPpdbController extends Controller
{
    /**
     * Daftar periode PPDB yang sedang dibuka (tanpa auth).
     * Query: institution_id=1 atau npsn=12345678
     */
    public function openPeriods(Request $request): JsonResponse
    {
        $institutionId = $request->get('institution_id') ? (int) $request->institution_id : null;
        $npsn = $request->get('npsn');

        if (!$institutionId && $npsn) {
            $institution = Institution::where('npsn', $npsn)->first();
            $institutionId = $institution?->id;
        }

        if (!$institutionId) {
            return response()->json([
                'message' => 'institution_id atau npsn wajib diisi.',
            ], 422);
        }

        $today = Carbon::today()->format('Y-m-d');
        $periods = PpdbPeriod::where('institution_id', $institutionId)
            ->where('status', 'open')
            ->whereDate('close_date', '>=', $today)
            ->whereDate('open_date', '<=', $today)
            ->with('academicYear:id,code,name')
            ->orderBy('open_date')
            ->get(['id', 'institution_id', 'academic_year_id', 'name', 'level', 'open_date', 'close_date', 're_registration_deadline']);

        $institution = Institution::find($institutionId);
        $institutionData = null;
        if ($institution) {
            $fullAddress = trim(implode(', ', array_filter([
                $institution->address,
                $institution->village,
                $institution->sub_district,
                $institution->district,
                $institution->province,
                $institution->postal_code,
            ])));
            $institutionData = [
                'id' => $institution->id,
                'name' => $institution->name,
                'foundation_name' => $institution->foundation_name,
                'npsn' => $institution->npsn,
                'nss' => $institution->nss,
                'address' => $fullAddress ?: $institution->address,
                'phone' => $institution->phone,
                'email' => $institution->email,
                'website' => $institution->website,
                'logo' => $institution->logo ? asset('storage/' . $institution->logo) : null,
            ];
        }
        return response()->json([
            'data' => $periods->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'level' => $p->level,
                'open_date' => $p->open_date?->format('Y-m-d'),
                'close_date' => $p->close_date?->format('Y-m-d'),
                're_registration_deadline' => $p->re_registration_deadline?->format('Y-m-d'),
                'academic_year' => $p->academicYear ? ['id' => $p->academicYear->id, 'code' => $p->academicYear->code, 'name' => $p->academicYear->name] : null,
            ]),
            'institution' => $institutionData,
        ]);
    }

    /**
     * Daftar jalur pendaftaran aktif per institusi (tanpa auth).
     */
    public function openChannels(Request $request): JsonResponse
    {
        $institutionId = $request->get('institution_id') ? (int) $request->institution_id : null;
        $npsn = $request->get('npsn');

        if (!$institutionId && $npsn) {
            $institution = Institution::where('npsn', $npsn)->first();
            $institutionId = $institution?->id;
        }

        if (!$institutionId) {
            return response()->json(['message' => 'institution_id atau npsn wajib diisi.'], 422);
        }

        $channels = PpdbChannel::where('institution_id', $institutionId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'quota', 'requirements']);

        return response()->json([
            'data' => $channels,
        ]);
    }

    /**
     * Prefill formulir dari data siswa di sekolah asal (tanpa auth).
     * Query: institution_id (sekolah tujuan), previous_school_npsn, nisn.
     * Rate limit untuk hindari abuse.
     */
    public function prefill(Request $request): JsonResponse
    {
        $institutionId = $request->get('institution_id') ? (int) $request->institution_id : null;
        $previousSchoolNpsn = $request->get('previous_school_npsn') ? trim((string) $request->previous_school_npsn) : '';
        $nisn = $request->get('nisn') ? trim((string) $request->nisn) : '';

        if (!$institutionId || $previousSchoolNpsn === '' || $nisn === '') {
            return response()->json([
                'found' => false,
                'message' => 'institution_id, previous_school_npsn, dan nisn wajib diisi.',
            ], 422);
        }

        $targetInstitution = Institution::where('id', $institutionId)->where('is_active', true)->first();
        if (!$targetInstitution) {
            return response()->json(['found' => false, 'message' => 'Sekolah tujuan tidak ditemukan.'], 404);
        }

        $originInstitution = Institution::where('npsn', $previousSchoolNpsn)->where('is_active', true)->first();
        if (!$originInstitution) {
            return response()->json(['found' => false, 'data' => null]);
        }

        $student = Student::where('institution_id', $originInstitution->id)
            ->where('nisn', $nisn)
            ->first();

        if (!$student) {
            return response()->json(['found' => false, 'data' => null]);
        }

        $fullAddress = trim(implode(', ', array_filter([
            $originInstitution->address,
            $originInstitution->village,
            $originInstitution->sub_district,
            $originInstitution->district,
            $originInstitution->province,
            $originInstitution->postal_code,
        ])));

        $data = [
            'name' => $student->name,
            'nik' => $student->nik,
            'nisn' => $student->nisn,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date?->format('Y-m-d'),
            'birth_place' => $student->birth_place,
            'address' => $student->address,
            'phone' => $student->phone,
            'email' => $student->email,
            'religion' => $student->religion,
            'previous_school' => $originInstitution->name,
            'previous_school_npsn' => $originInstitution->npsn,
            'previous_school_address' => $fullAddress ?: $originInstitution->address,
            'father_name' => $student->father_name,
            'father_nik' => $student->father_nik,
            'mother_name' => $student->mother_name,
            'mother_nik' => $student->mother_nik,
            'guardian_name' => $student->guardian_name,
            'guardian_phone' => $student->guardian_phone,
        ];

        return response()->json(['found' => true, 'data' => $data]);
    }

    /**
     * Cek hasil PPDB oleh calon (tanpa auth).
     * Query: registration_number ATAU nisn (salah satu wajib), optional: institution_id atau npsn (NPSN sekolah).
     */
    public function checkResult(Request $request): JsonResponse
    {
        $registrationNumber = $request->get('registration_number') ? trim((string) $request->registration_number) : '';
        $nisn = $request->get('nisn') ? trim((string) $request->nisn) : '';
        if ($registrationNumber === '' && $nisn === '') {
            return response()->json(['message' => 'Isi nomor pendaftaran atau NISN.'], 422);
        }

        $institutionId = $request->get('institution_id') ? (int) $request->institution_id : null;
        $npsnSchool = $request->get('npsn');

        if (!$institutionId && $npsnSchool) {
            $institution = Institution::where('npsn', $npsnSchool)->first();
            $institutionId = $institution?->id;
        }

        $query = PpdbApplicant::query()
            ->with(['period:id,name,institution_id,open_date,close_date', 'channel:id,name', 'student:id,nis,name']);

        if ($registrationNumber !== '') {
            $query->where('registration_number', $registrationNumber);
        } else {
            $query->where('nisn', $nisn)->orderByDesc('created_at');
        }

        if ($institutionId) {
            $query->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId));
        }

        $applicant = $query->first();

        if (!$applicant) {
            return response()->json([
                'message' => 'Data tidak ditemukan. Periksa nomor pendaftaran dan pilihan sekolah.',
            ], 404);
        }

        // Load institution for print header
        $institution = null;
        if ($applicant->period && $applicant->period->institution_id) {
            $institution = Institution::find($applicant->period->institution_id);
        }

        $data = [
            'registration_number' => $applicant->registration_number,
            'name' => $applicant->name,
            'period' => $applicant->period ? ['name' => $applicant->period->name] : null,
            'channel' => $applicant->channel ? ['name' => $applicant->channel->name] : null,
            'status' => $applicant->status,
            'rank' => $applicant->rank,
            'announcement_at' => $applicant->announcement_at?->format('Y-m-d H:i'),
            're_registration_deadline' => $applicant->re_registration_deadline?->format('Y-m-d'),
            're_registration_confirmed_at' => $applicant->re_registration_confirmed_at ? true : false,
            'result_notes' => $applicant->result_notes,
            // Full data for printing
            'nik' => $applicant->nik,
            'nisn' => $applicant->nisn,
            'gender' => $applicant->gender,
            'birth_date' => $applicant->birth_date?->format('Y-m-d'),
            'birth_place' => $applicant->birth_place,
            'address' => $applicant->address,
            'phone' => $applicant->phone,
            'email' => $applicant->email,
            'religion' => $applicant->religion,
            'previous_school' => $applicant->previous_school,
            'previous_school_npsn' => $applicant->previous_school_npsn,
            'previous_school_address' => $applicant->previous_school_address,
            'father_name' => $applicant->father_name,
            'father_phone' => $applicant->father_phone,
            'father_nik' => $applicant->father_nik,
            'mother_name' => $applicant->mother_name,
            'mother_phone' => $applicant->mother_phone,
            'mother_nik' => $applicant->mother_nik,
            'guardian_name' => $applicant->guardian_name,
            'guardian_phone' => $applicant->guardian_phone,
            'guardian_relation' => $applicant->guardian_relation,
            'notes' => $applicant->notes,
            'submitted_at' => $applicant->submitted_at?->format('Y-m-d H:i'),
        ];

        if ($applicant->status === 'converted' && $applicant->student) {
            $data['student'] = [
                'nis' => $applicant->student->nis,
                'name' => $applicant->student->name,
            ];
        } else {
            $data['student'] = null;
        }

        // Include institution data for print header
        if ($institution) {
            $fullAddress = trim(implode(', ', array_filter([
                $institution->address,
                $institution->village,
                $institution->sub_district,
                $institution->district,
                $institution->province,
                $institution->postal_code,
            ])));
            $data['institution'] = [
                'name' => $institution->name,
                'foundation_name' => $institution->foundation_name,
                'npsn' => $institution->npsn,
                'nss' => $institution->nss,
                'address' => $fullAddress ?: $institution->address,
                'phone' => $institution->phone,
                'email' => $institution->email,
                'website' => $institution->website,
                'logo' => $institution->logo ? asset('storage/' . $institution->logo) : null,
            ];
        }

        return response()->json(['data' => $data]);
    }

    /**
     * Konfirmasi daftar ulang oleh calon (tanpa auth).
     * Body/query: registration_number (wajib), optional: npsn atau institution_id (untuk scope).
     */
    public function confirmReRegistration(Request $request): JsonResponse
    {
        $registrationNumber = $request->get('registration_number') ? trim((string) $request->registration_number) : '';
        if ($registrationNumber === '') {
            return response()->json(['message' => 'Nomor pendaftaran wajib diisi.'], 422);
        }

        $institutionId = $request->get('institution_id') ? (int) $request->institution_id : null;
        $npsnSchool = $request->get('npsn');
        if (!$institutionId && $npsnSchool) {
            $institution = Institution::where('npsn', $npsnSchool)->first();
            $institutionId = $institution?->id;
        }

        $query = PpdbApplicant::query()
            ->with(['period:id,institution_id'])
            ->where('registration_number', $registrationNumber);

        if ($institutionId) {
            $query->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId));
        }

        $applicant = $query->first();

        if (!$applicant) {
            return response()->json([
                'message' => 'Data tidak ditemukan. Periksa nomor pendaftaran.',
            ], 404);
        }

        if (!in_array($applicant->status, ['passed', 'reserve', 're_registration'], true)) {
            return response()->json([
                'message' => 'Hanya calon yang lulus atau cadangan yang dapat konfirmasi daftar ulang.',
            ], 422);
        }

        if ($applicant->re_registration_deadline && $applicant->re_registration_deadline->isPast()) {
            return response()->json([
                'message' => 'Batas waktu konfirmasi daftar ulang telah lewat. Silakan hubungi sekolah.',
            ], 422);
        }

        // Idempotent: jika sudah dikonfirmasi, tetap kembalikan sukses
        if (!$applicant->re_registration_confirmed_at) {
            $applicant->update([
                'status' => 're_registration',
                're_registration_confirmed_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Konfirmasi daftar ulang berhasil.',
            'data' => [
                'registration_number' => $applicant->registration_number,
                're_registration_confirmed_at' => $applicant->fresh()->re_registration_confirmed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Upload dokumen calon oleh pendaftar (tanpa auth).
     * Form: registration_number (wajib), npsn (opsional), file, name, description (opsional).
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $registrationNumber = $request->get('registration_number') ? trim((string) $request->registration_number) : '';
        if ($registrationNumber === '') {
            return response()->json(['message' => 'Nomor pendaftaran wajib diisi.'], 422);
        }

        $institutionId = $request->get('institution_id') ? (int) $request->institution_id : null;
        $npsnSchool = $request->get('npsn');
        if (!$institutionId && $npsnSchool) {
            $institution = Institution::where('npsn', $npsnSchool)->first();
            $institutionId = $institution?->id;
        }

        $query = PpdbApplicant::query()
            ->with(['period:id,institution_id'])
            ->where('registration_number', $registrationNumber);

        if ($institutionId) {
            $query->whereHas('period', fn ($q) => $q->where('institution_id', $institutionId));
        }

        $applicant = $query->first();

        if (!$applicant) {
            return response()->json([
                'message' => 'Data tidak ditemukan. Periksa nomor pendaftaran.',
            ], 404);
        }

        // Hanya calon yang belum jadi siswa yang boleh upload berkas (submitted, verification, verified, draft)
        $allowedStatuses = ['draft', 'submitted', 'verification', 'verified'];
        if (!in_array($applicant->status, $allowedStatuses, true)) {
            return response()->json([
                'message' => 'Berkas hanya dapat diunggah selama masa pendaftaran atau verifikasi. Jika sudah lulus atau jadi siswa, silakan hubungi sekolah.',
            ], 422);
        }

        $documentCount = $applicant->documents()->count();
        if ($documentCount >= 20) {
            return response()->json([
                'message' => 'Maksimal 20 file dokumen per calon.',
            ], 400);
        }

        $rules = array_merge(
            FileUploadRules::rules(FileUploadRules::TYPE_MIXED, FileUploadRules::SIZE_SMALL, true, 'file'),
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

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $fileName = time() . '_' . $safeName . '.' . $extension;
            $filePath = $file->storeAs('ppdb_applicant_documents/' . $applicant->id, $fileName, 'public');

            $document = $applicant->documents()->create([
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
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Public PPDB uploadDocument failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengunggah dokumen. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Submit pendaftaran PPDB (tanpa auth). Rate limit 10/minute.
     */
    public function register(PublicPpdbRegisterRequest $request): JsonResponse
    {
        $period = PpdbPeriod::find($request->ppdb_period_id);
        if (!$period) {
            return response()->json(['message' => 'Periode PPDB tidak ditemukan.'], 404);
        }

        $today = Carbon::today();
        if ($period->status !== 'open') {
            return response()->json([
                'message' => 'Periode pendaftaran ini tidak sedang dibuka.',
            ], 422);
        }
        if ($period->open_date && $period->open_date->gt($today)) {
            return response()->json([
                'message' => 'Pendaftaran belum dibuka.',
            ], 422);
        }
        if ($period->close_date && $period->close_date->lt($today)) {
            return response()->json([
                'message' => 'Pendaftaran sudah ditutup.',
            ], 422);
        }

        $channel = PpdbChannel::find($request->ppdb_channel_id);
        if (!$channel || $channel->institution_id !== $period->institution_id) {
            return response()->json([
                'message' => 'Jalur pendaftaran tidak valid untuk periode ini.',
            ], 422);
        }

        $period->load('institution');
        $registrationNumber = $this->generateRegistrationNumber($period);
        $data = $request->validated();
        $data['registration_number'] = $registrationNumber;
        $data['status'] = 'submitted';
        $data['submitted_at'] = now();

        try {
            $applicant = PpdbApplicant::create($data);
            $applicant->load(['period:id,name', 'channel:id,name']);

            $this->notifyInstitutionAdmins($applicant);

            return response()->json([
                'message' => 'Pendaftaran berhasil. Simpan nomor pendaftaran Anda.',
                'data' => [
                    'id' => $applicant->id,
                    'registration_number' => $applicant->registration_number,
                    'name' => $applicant->name,
                    'period' => $applicant->period?->name,
                    'channel' => $applicant->channel?->name,
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('Public PPDB register failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal menyimpan pendaftaran. Silakan coba lagi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Nomor pendaftaran diawali NPSN agar cek hasil cukup input nomor saja.
     * Format: {NPSN}-{period_id}-{seq} contoh 10648387-1-00002
     */
    private function generateRegistrationNumber(PpdbPeriod $period): string
    {
        $institution = $period->institution;
        $npsn = $institution && $institution->npsn ? trim((string) $institution->npsn) : '0';
        $prefix = $npsn . '-' . $period->id . '-';

        $last = PpdbApplicant::where('ppdb_period_id', $period->id)
            ->where('registration_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('registration_number');

        $seq = 1;
        if ($last && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    private function notifyInstitutionAdmins(PpdbApplicant $applicant): void
    {
        $period = $applicant->period;
        if (!$period) {
            return;
        }

        $userIds = \App\Models\User::where('institution_id', $period->institution_id)
            ->whereIn('role', ['institution_admin', 'admin'])
            ->pluck('id');

        foreach ($userIds as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $user->notify(new PpdbRegistrationNotification($applicant));
            }
        }
    }
}
