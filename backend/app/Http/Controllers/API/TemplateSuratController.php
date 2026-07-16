<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TemplateSurat;
use App\Services\PlaceholderEngine;
use App\Services\TemplateSuratService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class TemplateSuratController extends Controller
{
    public function __construct(
        private TemplateSuratService $service
    ) {}

    public function index(Request $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        $filters = $request->only(['status', 'search', 'scope']);
        $perPage = min((int) $request->get('per_page', 50), 100);

        // Super admin tanpa filter institusi: fokus library platform
        if ($request->user()->isSuperAdmin() && !$institutionId && empty($filters['scope'])) {
            $filters['scope'] = 'platform';
        }

        $templates = $this->service->list($filters, $institutionId, $perPage);

        return response()->json([
            'data' => $templates->items(),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
            'placeholders' => PlaceholderEngine::availablePlaceholders(),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'kode' => 'required|string|max:50',
            'isi_html' => 'required|string',
            'status' => 'nullable|in:aktif,nonaktif',
            'letter_type_code' => 'nullable|string|size:2|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16',
            'subject_type' => 'nullable|in:siswa,pegawai,umum',
            'institution_id' => 'nullable|integer|exists:institution,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $data = $validator->validated();

        if ($user->isSuperAdmin()) {
            // Super admin: default buat template platform (institution_id null)
            // kecuali eksplisit kirim institution_id
            $data['institution_id'] = array_key_exists('institution_id', $data)
                ? $data['institution_id']
                : null;
        } else {
            $data['institution_id'] = $user->institution_id;
            if (!$data['institution_id']) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 422);
            }
        }

        $data['status'] = $data['status'] ?? 'aktif';
        $data['letter_type_code'] = $data['letter_type_code'] ?? '09';
        $data['subject_type'] = $data['subject_type'] ?? 'siswa';
        $data['kode'] = strtoupper(trim($data['kode']));

        if ($this->kodeTaken($data['kode'], $data['institution_id'] ?? null)) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => ['kode' => ['Kode template sudah dipakai di lingkup ini.']],
            ], 422);
        }

        $template = $this->service->create($data);

        return response()->json(['message' => 'Template berhasil dibuat', 'data' => $template], 201);
    }

    public function show(Request $request, $id)
    {
        $template = $this->service->find((int) $id);
        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }

        if (!$this->canReadTemplate($request, $template)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => $template,
            'placeholders' => PlaceholderEngine::availablePlaceholders(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $template = $this->service->find((int) $id);
        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }

        if (!$this->canWriteTemplate($request, $template)) {
            return response()->json([
                'message' => $template->isPlatform()
                    ? 'Template platform hanya dapat diubah oleh Super Admin. Salin ke sekolah Anda untuk menyesuaikan.'
                    : 'Forbidden',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'kode' => 'sometimes|required|string|max:50',
            'isi_html' => 'sometimes|required|string',
            'status' => 'nullable|in:aktif,nonaktif',
            'letter_type_code' => 'nullable|string|size:2|in:01,02,03,04,05,06,07,08,09,10,11,12,13,14,15,16',
            'subject_type' => 'nullable|in:siswa,pegawai,umum',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['kode'])) {
            $data['kode'] = strtoupper(trim($data['kode']));
            if ($this->kodeTaken($data['kode'], $template->institution_id, $template->id)) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'errors' => ['kode' => ['Kode template sudah dipakai di lingkup ini.']],
                ], 422);
            }
        }

        $template = $this->service->update($template, $data);

        return response()->json(['message' => 'Template berhasil diperbarui', 'data' => $template]);
    }

    public function destroy(Request $request, $id)
    {
        $template = $this->service->find((int) $id);
        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }

        if (!$this->canWriteTemplate($request, $template)) {
            return response()->json([
                'message' => $template->isPlatform()
                    ? 'Template platform hanya dapat dihapus oleh Super Admin.'
                    : 'Forbidden',
            ], 403);
        }

        $this->service->delete($template);

        return response()->json(['message' => 'Template berhasil dihapus']);
    }

    public function toggleStatus(Request $request, $id)
    {
        $template = $this->service->find((int) $id);
        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }

        if (!$this->canWriteTemplate($request, $template)) {
            return response()->json([
                'message' => $template->isPlatform()
                    ? 'Status template platform hanya dapat diubah oleh Super Admin.'
                    : 'Forbidden',
            ], 403);
        }

        $template = $this->service->toggleStatus($template);

        return response()->json(['message' => 'Status template diperbarui', 'data' => $template]);
    }

    /**
     * Salin template (biasanya platform) menjadi milik institusi user.
     */
    public function fork(Request $request, $id)
    {
        $template = $this->service->find((int) $id);
        if (!$template) {
            return response()->json(['message' => 'Template tidak ditemukan'], 404);
        }

        if (!$this->canReadTemplate($request, $template)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $institutionId = $request->user()->institution_id;
        if (!$institutionId) {
            return response()->json([
                'message' => 'Hanya akun sekolah yang dapat menyalin template ke institusinya.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'kode' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $copy = $this->service->fork(
                $template,
                (int) $institutionId,
                $validator->validated()['kode'] ?? null
            );
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Template berhasil disalin ke sekolah Anda',
            'data' => $copy,
        ], 201);
    }

    public function placeholders()
    {
        return response()->json(['data' => PlaceholderEngine::availablePlaceholders()]);
    }

    private function resolveInstitutionId(Request $request): ?int
    {
        if ($request->user()->isSuperAdmin()) {
            return $request->institution_id ? (int) $request->institution_id : null;
        }

        if ($request->user()->isAdmin()) {
            return $request->institution_id
                ? (int) $request->institution_id
                : $request->user()->institution_id;
        }

        return $request->user()->institution_id;
    }

    private function canReadTemplate(Request $request, TemplateSurat $template): bool
    {
        if ($request->user()->isSuperAdmin() || $request->user()->isAdmin()) {
            return true;
        }

        return $template->institution_id === null
            || $template->institution_id === $request->user()->institution_id;
    }

    /**
     * Platform templates: hanya Super Admin.
     * Template sekolah: hanya pemilik institusi yang sama.
     */
    private function canWriteTemplate(Request $request, TemplateSurat $template): bool
    {
        if ($template->isPlatform()) {
            return $request->user()->isSuperAdmin();
        }

        if ($request->user()->isSuperAdmin() || $request->user()->isAdmin()) {
            return true;
        }

        return $template->institution_id === $request->user()->institution_id;
    }

    private function kodeTaken(string $kode, ?int $institutionId, ?int $exceptId = null): bool
    {
        $query = TemplateSurat::query()->where('kode', $kode);

        if ($institutionId === null) {
            $query->whereNull('institution_id');
        } else {
            $query->where('institution_id', $institutionId);
        }

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        return $query->exists();
    }
}
