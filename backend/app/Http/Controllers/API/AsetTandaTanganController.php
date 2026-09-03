<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AsetTandaTangan;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AsetTandaTanganController extends Controller
{
    public function index(Request $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $query = AsetTandaTangan::forInstitution($institutionId)
            ->orderByDesc('is_default')
            ->orderBy('nama');

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis' => 'required|in:tanda_tangan,stempel',
            'nama' => 'required|string|max:255',
            'file' => 'required|image|max:2048',
            'pemilik_nama' => 'nullable|string|max:255',
            'pemilik_jabatan' => 'nullable|string|max:255',
            'pemilik_nip' => 'nullable|string|max:100',
            'lebar_mm' => 'nullable|integer|min:10|max:80',
            'tinggi_mm' => 'nullable|integer|min:10|max:80',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $data = collect($validator->validated())->except(['file'])->toArray();
        $data['institution_id'] = $institutionId;
        $data['status'] = $data['status'] ?? 'aktif';
        $data['lebar_mm'] = $data['lebar_mm'] ?? ($data['jenis'] === 'stempel' ? 35 : 40);
        $data['tinggi_mm'] = $data['tinggi_mm'] ?? ($data['jenis'] === 'stempel' ? 35 : 20);
        $data['is_default'] = filter_var($request->input('is_default', false), FILTER_VALIDATE_BOOLEAN);
        $data['file_path'] = $request->file('file')->store('surat/aset', 'public');

        $aset = DB::transaction(function () use ($data, $institutionId) {
            if (!empty($data['is_default'])) {
                AsetTandaTangan::where('institution_id', $institutionId)
                    ->where('jenis', $data['jenis'])
                    ->update(['is_default' => false]);
            }
            return AsetTandaTangan::create($data);
        });

        return response()->json(['message' => 'Aset berhasil dibuat', 'data' => $aset], 201);
    }

    public function show(Request $request, $id)
    {
        $aset = AsetTandaTangan::find($id);
        if (!$aset || !$this->canAccess($request, $aset->institution_id)) {
            return response()->json(['message' => 'Aset tidak ditemukan'], 404);
        }

        return response()->json(['data' => $aset]);
    }

    public function update(Request $request, $id)
    {
        $aset = AsetTandaTangan::find($id);
        if (!$aset || !$this->canAccess($request, $aset->institution_id)) {
            return response()->json(['message' => 'Aset tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'jenis' => 'sometimes|required|in:tanda_tangan,stempel',
            'nama' => 'sometimes|required|string|max:255',
            'file' => 'nullable|image|max:2048',
            'pemilik_nama' => 'nullable|string|max:255',
            'pemilik_jabatan' => 'nullable|string|max:255',
            'pemilik_nip' => 'nullable|string|max:100',
            'lebar_mm' => 'nullable|integer|min:10|max:80',
            'tinggi_mm' => 'nullable|integer|min:10|max:80',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = collect($validator->validated())->except(['file'])->toArray();
        if ($request->has('is_default')) {
            $data['is_default'] = filter_var($request->input('is_default'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($aset->file_path);
            $data['file_path'] = $request->file('file')->store('surat/aset', 'public');
        }

        DB::transaction(function () use ($aset, $data) {
            $jenis = $data['jenis'] ?? $aset->jenis;
            if (!empty($data['is_default'])) {
                AsetTandaTangan::where('institution_id', $aset->institution_id)
                    ->where('jenis', $jenis)
                    ->where('id', '!=', $aset->id)
                    ->update(['is_default' => false]);
            }
            $aset->update($data);
        });

        return response()->json(['message' => 'Aset berhasil diperbarui', 'data' => $aset->fresh()]);
    }

    public function destroy(Request $request, $id)
    {
        $aset = AsetTandaTangan::find($id);
        if (!$aset || !$this->canAccess($request, $aset->institution_id)) {
            return response()->json(['message' => 'Aset tidak ditemukan'], 404);
        }

        Storage::disk('public')->delete($aset->file_path);
        $aset->delete();

        return response()->json(['message' => 'Aset berhasil dihapus']);
    }

    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->filled('institution_id') ? $request->get('institution_id') : null
        );
    }

    private function canAccess(Request $request, int $institutionId): bool
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return InstitutionContext::canAccessInstitution($user, $institutionId);
    }
}
