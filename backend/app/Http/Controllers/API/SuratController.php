<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Student;
use App\Models\Surat;
use App\Services\SuratService;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class SuratController extends Controller
{
    public function __construct(
        private SuratService $service
    ) {}

    /**
     * GET /api/v1/surat/students
     * Daftar siswa untuk picker generate surat (akses modul correspondence).
     * Tidak memfilter semester agar semua siswa aktif tampil.
     */
    public function students(Request $request)
    {
        $institutionId = $this->requireInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $search = trim((string) $request->get('search', ''));
        $perPage = min((int) $request->get('per_page', 50), 100);

        $query = Student::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        $students = $query
            ->select(['id', 'name', 'nis', 'nisn', 'class', 'class_id', 'status'])
            ->with(['class:id,name'])
            ->limit($perPage)
            ->get()
            ->map(function (Student $s) {
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'nis' => $s->nis,
                    'nisn' => $s->nisn,
                    'kelas' => $s->class?->name ?? $s->getAttribute('class'),
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

        $search = trim((string) $request->get('search', ''));
        $perPage = min((int) $request->get('per_page', 50), 100);

        $query = Employee::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->orderBy('name');

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
            ->limit($perPage)
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
            return response()->json(['message' => 'Gagal export PDF'], 500);
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

        $pdf = DomPDF::loadView('surat.print', $this->service->printViewData($surat))
            ->setPaper('a4', 'portrait');

        $filename = 'Surat_' . preg_replace('/[^\w\-]+/', '_', $surat->nomor ?? (string) $surat->id) . '.pdf';

        return $pdf->stream($filename);
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

    private function resolveInstitutionId(Request $request): ?int
    {
        if (!$request->user()->isAdminOrSuperAdmin()) {
            return $request->user()->institution_id;
        }

        return $request->institution_id ? (int) $request->institution_id : $request->user()->institution_id;
    }

    private function requireInstitutionId(Request $request): ?int
    {
        return $this->resolveInstitutionId($request);
    }

    private function canAccess(Request $request, Surat $surat): bool
    {
        if ($request->user()->isAdminOrSuperAdmin()) {
            return true;
        }

        return $surat->institution_id === $request->user()->institution_id;
    }
}
