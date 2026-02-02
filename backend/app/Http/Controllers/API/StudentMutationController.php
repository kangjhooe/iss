<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveStudentMutationRequest;
use App\Http\Requests\StoreStudentMutationPullRequest;
use App\Http\Requests\StoreStudentMutationRequest;
use App\Http\Resources\StudentMutationResource;
use App\Models\StudentMutation;
use App\Services\StudentMutationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentMutationController extends Controller
{
    public function __construct(
        protected StudentMutationService $mutationService
    ) {}

    /**
     * List mutation requests (as origin, as target, or both).
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
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
     * Create mutation request from origin school (NPSN tujuan + NISN siswa).
     */
    public function store(StoreStudentMutationRequest $request)
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;

            $mutation = $this->mutationService->createFromOrigin(
                $institutionId,
                $request->validated('target_npsn'),
                $request->validated('nisn'),
                $user->id,
                $request->validated('notes')
            );

            $mutation->load([
                'originInstitution:id,name,npsn,level',
                'targetInstitution:id,name,npsn,level',
                'student:id,nisn,nis,name,gender,status',
                'requester:id,name,email',
            ]);

            return (new StudentMutationResource($mutation))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Student mutation store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengajukan mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create mutation request from target school (tarik siswa: NPSN asal + NISN siswa).
     * Admin sekolah tujuan memulai; admin sekolah asal nanti menyetujui.
     */
    public function pull(StoreStudentMutationPullRequest $request)
    {
        try {
            $user = $request->user();
            $targetInstitutionId = $user->institution_id;

            $mutation = $this->mutationService->createFromTarget(
                $targetInstitutionId,
                $request->validated('origin_npsn'),
                $request->validated('nisn'),
                $user->id,
                $request->validated('notes')
            );

            $mutation->load([
                'originInstitution:id,name,npsn,level',
                'targetInstitution:id,name,npsn,level',
                'student:id,nisn,nis,name,gender,status',
                'requester:id,name,email',
            ]);

            return (new StudentMutationResource($mutation))
                ->response()
                ->setStatusCode(201);
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
        $instId = $user->institution_id;
        $m = $student_mutation;
        if ($instId !== $m->origin_institution_id && $instId !== $m->target_institution_id && !$user->isSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student_mutation->load([
            'originInstitution:id,name,npsn,level',
            'targetInstitution:id,name,npsn,level',
            'student:id,nisn,nis,name,gender,status,institution_id',
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
     * Get institutions by NPSN search (for dropdown/autocomplete, same jenjang only).
     */
    public function searchTargetInstitutions(Request $request)
    {
        $user = $request->user();
        $institutionId = $user->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $origin = \App\Models\Institution::find($institutionId);
        if (!$origin) {
            return response()->json(['message' => 'Sekolah asal tidak ditemukan.'], 404);
        }

        $search = $request->get('q', '');
        $query = \App\Models\Institution::where('id', '!=', $institutionId)
            ->where('is_active', true)
            ->where(function ($q) use ($origin) {
                $group = \App\Models\Institution::getMutasiLevelGroup($origin->level);
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
        $user = $request->user();
        $institutionId = $user->institution_id;
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $target = \App\Models\Institution::find($institutionId);
        if (!$target) {
            return response()->json(['message' => 'Sekolah Anda tidak ditemukan.'], 404);
        }

        $search = $request->get('q', '');
        $query = \App\Models\Institution::where('id', '!=', $institutionId)
            ->where('is_active', true)
            ->where(function ($q) use ($target) {
                $group = \App\Models\Institution::getMutasiLevelGroup($target->level);
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
            $user = $request->user();
            $institutionId = $user->institution_id;
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
     * Riwayat mutasi per siswa (hanya jika institusi user terlibat).
     */
    public function historyByStudent(Request $request, int $student_id)
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
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
     * Riwayat mutasi per siswa by NISN.
     */
    public function historyByNisn(Request $request)
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $nisn = $request->get('nisn');
            if (!$nisn || !is_string($nisn)) {
                return response()->json(['message' => 'NISN wajib diisi.'], 422);
            }
            $list = $this->mutationService->historyByNisn(trim($nisn), $institutionId);
            return StudentMutationResource::collection($list);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Exception $e) {
            Log::error('Student mutation history by NISN failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil riwayat mutasi.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
