<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\KopSurat;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class KopSuratController extends Controller
{
    public function index(Request $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $this->ensureDefaultKop($institutionId);

        $query = KopSurat::forInstitution($institutionId)->orderByDesc('is_default')->orderBy('nama');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(['data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'baris_1' => 'nullable|string|max:255',
            'baris_2' => 'nullable|string|max:255',
            'baris_3' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'isi_html' => 'nullable|string',
            'tampilkan_garis' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:aktif,nonaktif',
            'logo_kiri' => 'nullable|image|max:2048',
            'logo_kanan' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
        }

        $data = collect($validator->validated())->except(['logo_kiri', 'logo_kanan'])->toArray();
        $data['institution_id'] = $institutionId;
        $data['status'] = $data['status'] ?? 'aktif';
        $data['tampilkan_garis'] = filter_var($request->input('tampilkan_garis', true), FILTER_VALIDATE_BOOLEAN);
        $data['is_default'] = filter_var($request->input('is_default', false), FILTER_VALIDATE_BOOLEAN);

        if ($request->hasFile('logo_kiri')) {
            $data['logo_kiri'] = $request->file('logo_kiri')->store('kop/logos', 'public');
        }
        if ($request->hasFile('logo_kanan')) {
            $data['logo_kanan'] = $request->file('logo_kanan')->store('kop/logos', 'public');
        }

        $kop = DB::transaction(function () use ($data, $institutionId) {
            if (!empty($data['is_default'])) {
                KopSurat::where('institution_id', $institutionId)->update(['is_default' => false]);
            }
            return KopSurat::create($data);
        });

        return response()->json(['message' => 'Kop berhasil dibuat', 'data' => $kop], 201);
    }

    public function show(Request $request, $id)
    {
        $kop = KopSurat::find($id);
        if (!$kop || !$this->canAccess($request, $kop->institution_id)) {
            return response()->json(['message' => 'Kop tidak ditemukan'], 404);
        }

        return response()->json(['data' => $kop]);
    }

    public function update(Request $request, $id)
    {
        $kop = KopSurat::find($id);
        if (!$kop || !$this->canAccess($request, $kop->institution_id)) {
            return response()->json(['message' => 'Kop tidak ditemukan'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'baris_1' => 'nullable|string|max:255',
            'baris_2' => 'nullable|string|max:255',
            'baris_3' => 'nullable|string|max:255',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|max:255',
            'isi_html' => 'nullable|string',
            'tampilkan_garis' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'status' => 'nullable|in:aktif,nonaktif',
            'logo_kiri' => 'nullable|image|max:2048',
            'logo_kanan' => 'nullable|image|max:2048',
            'hapus_logo_kiri' => 'nullable|boolean',
            'hapus_logo_kanan' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $data = collect($validator->validated())
            ->except(['logo_kiri', 'logo_kanan', 'hapus_logo_kiri', 'hapus_logo_kanan'])
            ->toArray();

        if ($request->has('tampilkan_garis')) {
            $data['tampilkan_garis'] = filter_var($request->input('tampilkan_garis'), FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('is_default')) {
            $data['is_default'] = filter_var($request->input('is_default'), FILTER_VALIDATE_BOOLEAN);
        }

        if (filter_var($request->input('hapus_logo_kiri'), FILTER_VALIDATE_BOOLEAN) && $kop->logo_kiri) {
            Storage::disk('public')->delete($kop->logo_kiri);
            $data['logo_kiri'] = null;
        }
        if (filter_var($request->input('hapus_logo_kanan'), FILTER_VALIDATE_BOOLEAN) && $kop->logo_kanan) {
            Storage::disk('public')->delete($kop->logo_kanan);
            $data['logo_kanan'] = null;
        }

        if ($request->hasFile('logo_kiri')) {
            if ($kop->logo_kiri) {
                Storage::disk('public')->delete($kop->logo_kiri);
            }
            $data['logo_kiri'] = $request->file('logo_kiri')->store('kop/logos', 'public');
        }
        if ($request->hasFile('logo_kanan')) {
            if ($kop->logo_kanan) {
                Storage::disk('public')->delete($kop->logo_kanan);
            }
            $data['logo_kanan'] = $request->file('logo_kanan')->store('kop/logos', 'public');
        }

        DB::transaction(function () use ($kop, $data) {
            if (!empty($data['is_default'])) {
                KopSurat::where('institution_id', $kop->institution_id)
                    ->where('id', '!=', $kop->id)
                    ->update(['is_default' => false]);
            }
            $kop->update($data);
        });

        return response()->json(['message' => 'Kop berhasil diperbarui', 'data' => $kop->fresh()]);
    }

    public function destroy(Request $request, $id)
    {
        $kop = KopSurat::find($id);
        if (!$kop || !$this->canAccess($request, $kop->institution_id)) {
            return response()->json(['message' => 'Kop tidak ditemukan'], 404);
        }

        if ($kop->logo_kiri) {
            Storage::disk('public')->delete($kop->logo_kiri);
        }
        if ($kop->logo_kanan) {
            Storage::disk('public')->delete($kop->logo_kanan);
        }
        $kop->delete();

        return response()->json(['message' => 'Kop berhasil dihapus']);
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

    /**
     * Pastikan ada record kop default yang mengacu ke layout standar institusi.
     */
    private function ensureDefaultKop(int $institutionId): void
    {
        if (!Institution::where('id', $institutionId)->exists()) {
            return;
        }

        $default = KopSurat::query()
            ->where('institution_id', $institutionId)
            ->where('is_default', true)
            ->first();

        if ($default) {
            if ($default->nama !== 'Kop Standar Institusi') {
                $default->update(['nama' => 'Kop Standar Institusi']);
            }
            return;
        }

        KopSurat::create([
            'institution_id' => $institutionId,
            'nama' => 'Kop Standar Institusi',
            'status' => 'aktif',
            'tampilkan_garis' => true,
            'is_default' => true,
        ]);
    }
}
