<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreBankSoalRequest;
use App\Http\Requests\UpdateBankSoalRequest;
use App\Http\Resources\BankSoalResource;
use App\Models\BankSoal;
use App\Models\Institution;
use App\Models\User;
use App\Services\BankSoalBackupService;
use App\Services\BankSoalShareService;
use App\Services\ClassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

class BankSoalController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected ClassService $classService,
        protected BankSoalBackupService $backupService,
        protected BankSoalShareService $shareService
    ) {}

    /**
     * User can access bank if: same institution, or shared with user, or owner, or institution_admin for that institution.
     */
    private function canAccessBank(Request $request, BankSoal $bank): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        $institutionId = $this->resolveInstitutionId($request);
        if ($bank->institution_id == $institutionId) {
            return true;
        }
        if ($bank->created_by_user_id === $user->id) {
            return true;
        }
        if ($bank->sharedWithUsers()->where('id', $user->id)->exists()) {
            return true;
        }
        if ($user->isInstitutionAdmin() && $user->institution_id == $bank->institution_id) {
            return true;
        }
        return false;
    }

    /**
     * Only owner or institution_admin (for the bank's institution) can invite/revoke shares.
     */
    private function canManageShare(Request $request, BankSoal $bank): bool
    {
        $user = $request->user();
        if (!$user) {
            return false;
        }
        if ($bank->created_by_user_id === $user->id) {
            return true;
        }
        return $user->isInstitutionAdmin() && $user->institution_id == $bank->institution_id;
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $userId = $request->user()->id;
        $query = BankSoal::query()
            ->where(function ($q) use ($institutionId, $userId) {
                $q->where('institution_id', $institutionId)
                    ->orWhereHas('sharedWithUsers', fn ($q2) => $q2->where('id', $userId));
            })
            ->with('subject');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->get('subject_id'));
        }
        if ($request->filled('grade')) {
            $query->where('grade', $request->get('grade'));
        }
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        $query->orderBy('code');
        $perPage = min($request->get('per_page', 15), 100);
        $items = $query->withCount([
            'questions',
            'questions as pg_count' => fn ($q) => $q->where('type', 'pg'),
            'questions as pg_kompleks_count' => fn ($q) => $q->where('type', 'pg_kompleks'),
            'questions as matching_count' => fn ($q) => $q->where('type', 'matching'),
            'questions as isian_count' => fn ($q) => $q->where('type', 'isian'),
            'questions as uraian_count' => fn ($q) => $q->where('type', 'uraian'),
        ])->paginate($perPage);

        return BankSoalResource::collection($items);
    }

    public function store(StoreBankSoalRequest $request): JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $data = $request->validated();
        $data['institution_id'] = $institutionId;
        $data['created_by_user_id'] = $request->user()->id;
        $err = $this->validateGradeForInstitution($institutionId, $data['grade'] ?? null);
        if ($err) {
            return $err;
        }
        $bank = BankSoal::create($data);
        $bank->load('subject');

        return (new BankSoalResource($bank))->response()->setStatusCode(201);
    }

    public function show(Request $request, BankSoal $bank_soal): BankSoalResource|JsonResponse
    {
        if (!$this->canAccessBank($request, $bank_soal)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }
        $bank_soal->load(['subject', 'createdByUser']);
        $bank_soal->loadCount('questions');
        return new BankSoalResource($bank_soal);
    }

    public function update(UpdateBankSoalRequest $request, BankSoal $bank_soal): BankSoalResource|JsonResponse
    {
        if (!$this->canAccessBank($request, $bank_soal)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }
        $data = $request->validated();
        $err = $this->validateGradeForInstitution($bank_soal->institution_id, $data['grade'] ?? null);
        if ($err) {
            return $err;
        }
        $bank_soal->update($data);
        $bank_soal->load('subject');
        return new BankSoalResource($bank_soal);
    }

    public function destroy(Request $request, BankSoal $bank_soal): JsonResponse
    {
        if (!$this->canAccessBank($request, $bank_soal)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }
        $usedInExam = $bank_soal->questions()->whereHas('examQuestions')->exists();
        if ($usedInExam) {
            return response()->json([
                'message' => 'Bank tidak bisa dihapus karena ada soal yang terpasang di ujian. Lepas soal dari paket ujian terlebih dahulu.',
            ], 422);
        }
        $bank_soal->delete();
        return response()->json(['message' => 'Bank soal dihapus.']);
    }

    /**
     * Backup isi bank soal (soal, opsi, stimulus, aset) ke file ZIP. Tidak termasuk kode/nama/mapel/kelas/keterangan.
     */
    public function backup(Request $request, BankSoal $bank_soal)
    {
        if (!$this->canAccessBank($request, $bank_soal)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }
        try {
            $zipPath = $this->backupService->backup($bank_soal);
            $filename = 'bank-soal-backup-' . $bank_soal->code . '-' . date('Y-m-d-His') . '.zip';
            $response = response()->download($zipPath, $filename, ['Content-Type' => 'application/zip']);
            $response->deleteFileAfterSend(true);
            return $response;
        } catch (\Throwable $e) {
            Log::error('Bank soal backup error', ['bank_id' => $bank_soal->id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat backup: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Restore isi backup ZIP ke bank soal yang sudah ada (target).
     */
    public function restore(Request $request): JsonResponse
    {
        $request->validate([
            'bank_soal_id' => 'required|exists:bank_soal,id',
            'file' => 'required|file|mimes:zip|max:51200', // max 50MB
        ]);
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        $targetBank = BankSoal::findOrFail($request->input('bank_soal_id'));
        if (!$this->canAccessBank($request, $targetBank)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }
        try {
            $result = $this->backupService->restore($request->file('file'), $targetBank);
            return response()->json([
                'message' => 'Restore berhasil.',
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('Bank soal restore error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal restore: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Invite a teacher to access this bank by NIK. Only owner or institution_admin can invite.
     * Resolves User from NIK (system-wide); rejects if guru has no user account (Option B).
     */
    public function invite(Request $request, BankSoal $bank_soal): JsonResponse
    {
        if (!$this->canManageShare($request, $bank_soal)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan atau Anda tidak memiliki wewenang.'], 404);
        }
        $request->validate(['nik' => 'required|string|max:50']);
        $nik = trim($request->input('nik'));
        $user = $this->shareService->resolveUserByTeacherNik($nik);
        if (!$user) {
            return response()->json(['message' => 'Guru dengan NIK ini belum memiliki akun.'], 422);
        }
        if ($user->id === $bank_soal->created_by_user_id) {
            return response()->json(['message' => 'Pemilik bank sudah memiliki akses penuh.'], 422);
        }
        if ($bank_soal->sharedWithUsers()->where('id', $user->id)->exists()) {
            return response()->json(['message' => 'Guru ini sudah diundang.'], 422);
        }
        $bank_soal->sharedWithUsers()->attach($user->id);
        $user->load('institution');
        return response()->json([
            'message' => 'Akses berhasil dibagikan.',
            'user' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'institution' => $user->institution ? ['id' => $user->institution->id, 'name' => $user->institution->name] : null],
        ], 201);
    }

    /**
     * Revoke shared access for a user. Only owner or institution_admin can revoke.
     */
    public function revoke(Request $request, BankSoal $bank_soal, int $user_id): JsonResponse
    {
        if (!$this->canManageShare($request, $bank_soal)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan atau Anda tidak memiliki wewenang.'], 404);
        }
        if ($user_id === $bank_soal->created_by_user_id) {
            return response()->json(['message' => 'Akses pemilik tidak dapat dicabut.'], 422);
        }
        $bank_soal->sharedWithUsers()->detach($user_id);
        return response()->json(['message' => 'Akses berhasil dicabut.']);
    }

    /**
     * List users with access: owner + shared collaborators.
     */
    public function listShares(Request $request, BankSoal $bank_soal): JsonResponse
    {
        if (!$this->canAccessBank($request, $bank_soal)) {
            return response()->json(['message' => 'Bank soal tidak ditemukan.'], 404);
        }
        $owner = $bank_soal->createdByUser;
        $shared = $bank_soal->sharedWithUsers()->with('institution')->get();
        $list = [];
        if ($owner) {
            $list[] = [
                'user_id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => 'owner',
                'institution' => $owner->institution ? ['id' => $owner->institution->id, 'name' => $owner->institution->name] : null,
            ];
        }
        foreach ($shared as $u) {
            $list[] = [
                'user_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => 'shared',
                'institution' => $u->institution ? ['id' => $u->institution->id, 'name' => $u->institution->name] : null,
            ];
        }
        return response()->json([
            'data' => $list,
            'can_manage' => $this->canManageShare($request, $bank_soal),
        ]);
    }

    private function validateGradeForInstitution(int $institutionId, $grade): ?JsonResponse
    {
        if ($grade === null || $grade === '') {
            return null;
        }
        $institution = Institution::find($institutionId);
        $validGrades = $institution ? $this->classService->getAvailableGrades($institution->level) : null;
        if ($validGrades === null) {
            return response()->json(['message' => 'Jenjang institusi (PAUD/TK) tidak menggunakan tingkat kelas numerik.'], 422);
        }
        $gradeInt = (int) $grade;
        if (!in_array($gradeInt, $validGrades)) {
            return response()->json(['message' => 'Kelas tidak sesuai jenjang institusi. Tingkat yang valid: ' . implode(', ', $validGrades)], 422);
        }
        return null;
    }
}
