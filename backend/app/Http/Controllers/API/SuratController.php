<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Surat;
use App\Services\SuratService;
use App\Support\InstitutionContext;
use App\Support\RegionAddress;
use App\Support\StandardLetterhead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class SuratController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        private SuratService $service
    ) {}

    /**
     * GET /api/v1/surat/classes
     * Daftar kelas ringkas untuk picker generate surat.
     */
    public function classes(Request $request)
    {
        $institutionId = $this->requireInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $institution = Institution::find($institutionId);
        $query = SchoolClass::query()
            ->where('institution_id', $institutionId)
            ->orderBy('grade')
            ->orderBy('name');

        if ($institution?->active_academic_year_id) {
            $query->where('academic_year_id', $institution->active_academic_year_id);
        }

        return response()->json([
            'data' => $query->get(['id', 'name', 'grade']),
        ]);
    }

    /**
     * GET /api/v1/surat/letterhead-context
     * Profil institusi lengkap untuk render kop standar di editor surat.
     */
    public function letterheadContext(Request $request)
    {
        $institutionId = $this->requireInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $institution = Institution::find($institutionId);
        if (!$institution) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $institution->id,
                'name' => $institution->name,
                'foundation_name' => $institution->foundation_name,
                'level' => $institution->level,
                'npsn' => $institution->npsn,
                'nss' => $institution->nss,
                'address' => $institution->address,
                'village' => $institution->village,
                'sub_district' => $institution->sub_district,
                'district' => $institution->district,
                'province' => $institution->province,
                'postal_code' => $institution->postal_code,
                'full_address' => RegionAddress::format($institution),
                'phone' => $institution->phone,
                'email' => $institution->email,
                'website' => $institution->website,
                'logo' => StandardLetterhead::resolveLogoUrl($institution),
            ],
        ]);
    }

    /**
     * GET /api/v1/surat/students
     * Daftar siswa untuk picker generate surat (akses modul correspondence).
     * Filter class_id dan/atau search (nama/NIS/NISN/NIK).
     */
    public function students(Request $request)
    {
        $institutionId = $this->requireInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $search = trim((string) ($request->get('search') ?: $request->get('q', '')));
        $classId = $request->get('class_id');
        $studentId = $request->get('student_id');

        if (!$classId && $search === '' && !$studentId) {
            return response()->json([
                'message' => 'Pilih kelas atau ketik nama/NIS siswa.',
                'data' => [],
                'meta' => ['total' => 0],
            ]);
        }

        $query = Student::query()
            ->leftJoin('class', 'student.class_id', '=', 'class.id')
            ->where('student.institution_id', $institutionId)
            ->where(function ($w) {
                $w->where('student.status', 'Aktif')->orWhereNull('student.status');
            })
            ->orderBy('student.name')
            ->select([
                'student.id',
                'student.name',
                'student.nis',
                'student.nisn',
                'student.nik',
                'student.class_id',
                'student.status',
                'class.name as class_name',
            ]);

        if ($studentId) {
            $query->where('student.id', (int) $studentId)->limit(1);
        } elseif ($classId) {
            $query->where('student.class_id', (int) $classId)->limit(200);
        } else {
            $query->limit(50);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('student.name', 'like', "%{$search}%")
                    ->orWhere('student.nis', 'like', "%{$search}%")
                    ->orWhere('student.nisn', 'like', "%{$search}%")
                    ->orWhere('student.nik', 'like', "%{$search}%");
            });
        }

        $students = $query->get()->map(function ($s) {
            return [
                'id' => (int) $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'nisn' => $s->nisn,
                'nik' => $s->nik,
                'class_id' => $s->class_id ? (int) $s->class_id : null,
                'kelas' => $s->class_name,
                'status' => $s->status,
            ];
        });

        return response()->json([
            'data' => $students,
            'meta' => ['total' => $students->count()],
        ]);
    }

    /**
     * GET /api/v1/surat/employees
     * Daftar guru/pegawai untuk picker generate surat.
     */
    public function employees(Request $request)
    {
        $institutionId = $this->requireInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $search = trim((string) ($request->get('search') ?: $request->get('q', '')));
        $type = trim((string) $request->get('type', ''));
        $employeeId = $request->get('employee_id');
        $perPage = min((int) $request->get('per_page', 50), 100);

        if (!$employeeId && $search === '' && $type === '') {
            return response()->json([
                'message' => 'Ketik nama/NIP atau pilih filter tipe guru/pegawai.',
                'data' => [],
                'meta' => ['total' => 0],
            ]);
        }

        $query = Employee::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->orderBy('name');

        if ($employeeId) {
            $query->where('id', (int) $employeeId)->limit(1);
        } else {
            $query->limit($perPage);
        }

        if ($type !== '' && in_array($type, ['Guru', 'Pegawai'], true)) {
            $query->where('type', $type);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('nuptk', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%");
            });
        }

        $employees = $query
            ->select([
                'id', 'name', 'nip', 'nuptk', 'type', 'subject',
                'employment_status', 'status',
            ])
            ->get()
            ->map(function (Employee $e) {
                return [
                    'id' => $e->id,
                    'name' => $e->name,
                    'nip' => $e->nip,
                    'nuptk' => $e->nuptk,
                    'type' => $e->type,
                    'subject' => $e->subject,
                    'employment_status' => $e->employment_status,
                    'jabatan' => $e->type === 'Guru' && $e->subject
                        ? 'Guru ' . $e->subject
                        : ($e->type ?: $e->employment_status),
                    'status' => $e->status,
                ];
            });

        return response()->json([
            'data' => $employees,
            'meta' => ['total' => $employees->count()],
        ]);
    }

    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            $filters = $request->only(['search', 'status', 'template_id']);
            $perPage = min((int) $request->get('per_page', 20), 100);

            $surat = $this->service->list($filters, $institutionId, $perPage);

            return response()->json([
                'data' => $surat->items(),
                'meta' => [
                    'current_page' => $surat->currentPage(),
                    'last_page' => $surat->lastPage(),
                    'per_page' => $surat->perPage(),
                    'total' => $surat->total(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to list surat', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil daftar surat'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'isi_html' => 'required|string',
            'template_id' => 'nullable|exists:template_surat,id',
            'student_id' => 'nullable|exists:student,id',
            'employee_id' => 'nullable|exists:employee,id',
            'nomor' => 'nullable|string|max:100',
            'letter_type_code' => 'nullable|string|size:2',
            'status' => 'nullable|in:draft,terbit',
            'kop_id' => 'nullable|exists:kop_surat,id',
            'tampilkan_kop' => 'nullable|boolean',
            'tanda_tangan_id' => 'nullable|exists:aset_tanda_tangan,id',
            'tampilkan_tanda_tangan' => 'nullable|boolean',
            'stempel_id' => 'nullable|exists:aset_tanda_tangan,id',
            'tampilkan_stempel' => 'nullable|boolean',
            'posisi_ttd' => 'nullable|in:kanan,kiri,ganda',
            'tanggal' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $institutionId = $this->requireInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $data = $validator->validated();
        unset($data['nomor'], $data['status']); // nomor hanya via terbitkan
        $data['institution_id'] = $institutionId;
        $data['created_by'] = $request->user()->id;
        $data['letter_type_code'] = $data['letter_type_code'] ?? '09';
        $data['tampilkan_kop'] = array_key_exists('tampilkan_kop', $data) ? (bool) $data['tampilkan_kop'] : true;
        $data['tampilkan_tanda_tangan'] = (bool) ($data['tampilkan_tanda_tangan'] ?? false);
        $data['tampilkan_stempel'] = (bool) ($data['tampilkan_stempel'] ?? false);
        $data['posisi_ttd'] = $data['posisi_ttd'] ?? 'kanan';

        $surat = $this->service->create($data);

        return response()->json(['message' => 'Surat berhasil disimpan', 'data' => $surat], 201);
    }

    public function show(Request $request, $id)
    {
        $surat = $this->service->find((int) $id);
        if (!$surat) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        }

        if (!$this->canAccess($request, $surat)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $surat]);
    }

    public function update(Request $request, $id)
    {
        $surat = $this->service->find((int) $id);
        if (!$surat) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        }

        if (!$this->canAccess($request, $surat)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'sometimes|required|string|max:255',
            'isi_html' => 'sometimes|required|string',
            'letter_type_code' => 'nullable|string|size:2',
            'kop_id' => 'nullable|exists:kop_surat,id',
            'tampilkan_kop' => 'nullable|boolean',
            'tanda_tangan_id' => 'nullable|exists:aset_tanda_tangan,id',
            'tampilkan_tanda_tangan' => 'nullable|boolean',
            'stempel_id' => 'nullable|exists:aset_tanda_tangan,id',
            'tampilkan_stempel' => 'nullable|boolean',
            'posisi_ttd' => 'nullable|in:kanan,kiri,ganda',
            'tanggal' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        foreach (['tampilkan_kop', 'tampilkan_tanda_tangan', 'tampilkan_stempel'] as $boolField) {
            if (array_key_exists($boolField, $data)) {
                $data[$boolField] = (bool) $data[$boolField];
            }
        }

        $surat = $this->service->update($surat, $data);

        return response()->json(['message' => 'Surat berhasil diperbarui', 'data' => $surat]);
    }

    public function destroy(Request $request, $id)
    {
        $surat = $this->service->find((int) $id);
        if (!$surat) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        }

        if (!$this->canAccess($request, $surat)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $this->service->delete($surat);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Surat berhasil dihapus']);
    }

    /**
     * POST /api/v1/surat/generate
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'template_id' => 'required|exists:template_surat,id',
            'subject_type' => 'nullable|in:siswa,pegawai,umum',
            'student_id' => 'nullable|exists:student,id',
            'employee_id' => 'nullable|exists:employee,id',
            'judul' => 'nullable|string|max:255',
            'tanggal' => 'nullable|date',
            'letter_type_code' => 'nullable|string|size:2',
            'extra' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $institutionId = $this->requireInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        try {
            $surat = $this->service->generate(
                $validator->validated(),
                $request->user()->id,
                $institutionId
            );

            return response()->json([
                'message' => 'Draft surat berhasil dibuat. Nomor resmi muncul setelah diterbitkan.',
                'data' => $surat->load(['template', 'student', 'employee']),
            ], 201);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to generate surat', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal generate surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * POST /api/v1/surat/{id}/terbitkan
     * Membuat register correspondence + nomor resmi.
     */
    public function publish(Request $request, $id)
    {
        $surat = $this->service->find((int) $id);
        if (!$surat) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        }

        if (!$this->canAccess($request, $surat)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'letter_type_code' => 'nullable|string|size:2|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16',
            'tanggal' => 'nullable|date',
            'to' => 'nullable|string|max:255',
            'priority' => 'nullable|in:biasa,penting,sangat_penting',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $surat = $this->service->publish(
                $surat,
                $request->user()->id,
                $validator->validated()
            );

            return response()->json([
                'message' => 'Surat berhasil diterbitkan dan masuk register Arsip',
                'data' => $surat,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to publish surat', ['error' => $e->getMessage(), 'surat_id' => $id]);
            return response()->json([
                'message' => 'Gagal menerbitkan surat',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * GET /api/v1/surat/{id}/pdf — Export & download PDF (DomPDF)
     */
    public function exportPdf(Request $request, $id)
    {
        $surat = $this->service->find((int) $id);
        if (!$surat) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        }

        if (!$this->canAccess($request, $surat)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            return $this->service->downloadPdf($surat);
        } catch (\Exception $e) {
            Log::error('Failed to export surat PDF', ['error' => $e->getMessage(), 'surat_id' => $id]);
            $message = 'Gagal export PDF';
            if (str_contains($e->getMessage(), 'GD extension')) {
                $message = 'Export PDF gagal: ekstensi PHP GD belum aktif di server. Aktifkan extension=gd di php.ini lalu restart Apache.';
            }
            return response()->json(['message' => $message], 500);
        }
    }

    /**
     * GET /api/v1/surat/{id}/print — HTML siap print / stream PDF preview
     */
    public function print(Request $request, $id)
    {
        $surat = $this->service->find((int) $id);
        if (!$surat) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        }

        if (!$this->canAccess($request, $surat)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            return $this->service->streamPdf($surat);
        } catch (\Exception $e) {
            Log::error('Failed to stream surat PDF', ['error' => $e->getMessage(), 'surat_id' => $id]);
            $message = 'Gagal membuka PDF cetak';
            if (str_contains($e->getMessage(), 'GD extension')) {
                $message = 'Cetak gagal: ekstensi PHP GD belum aktif di server. Aktifkan extension=gd di php.ini lalu restart Apache.';
            }
            return response()->json(['message' => $message], 500);
        }
    }

    /**
     * GET /api/v1/surat/{id}/layout — parts for frontend preview (kop/ttd/stempel)
     */
    public function layout(Request $request, $id)
    {
        $surat = $this->service->find((int) $id);
        if (!$surat) {
            return response()->json(['message' => 'Surat tidak ditemukan'], 404);
        }

        if (!$this->canAccess($request, $surat)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $parts = $this->service->previewParts($surat);

        return response()->json([
            'data' => [
                'kop_html' => $parts['kopHtml'],
                'isi_html' => $parts['isiHtml'],
                'ttd_html' => $parts['ttdHtml'],
            ],
        ]);
    }

    /**
     * POST /api/v1/surat/upload-image — upload gambar untuk editor
     */
    public function uploadImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'upload' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $path = $request->file('upload')->store('surat/images', 'public');
        $url = Storage::disk('public')->url($path);

        return response()->json([
            'url' => $url,
            'uploaded' => true,
        ]);
    }

    private function requireInstitutionId(Request $request): ?int
    {
        return $this->resolveInstitutionId($request);
    }

    private function canAccess(Request $request, Surat $surat): bool
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return InstitutionContext::canAccessInstitution($user, (int) $surat->institution_id);
    }
}
