<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\DecideEmployeeLeaveRequest;
use App\Http\Requests\StoreEmployeeLeaveRequest;
use App\Http\Resources\EmployeeLeaveRequestResource;
use App\Models\EmployeeLeaveRequest;
use App\Services\KepegawaianService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmployeeLeaveController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected KepegawaianService $service
    ) {}

    public function meta(): JsonResponse
    {
        return response()->json([
            'leave_types' => EmployeeLeaveRequest::TYPES,
            'statuses' => EmployeeLeaveRequest::STATUSES,
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['status', 'leave_type', 'employee_id', 'search']);
            $perPage = min((int) $request->get('per_page', 15), 100);
            $items = $this->service->listLeaves($institutionId, $filters, $perPage);

            return EmployeeLeaveRequestResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Employee leave index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil data cuti.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function my(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['teacherProfile', 'employeeProfile']);
            $profile = $user->teacherProfile ?? $user->employeeProfile;
            if (!$profile) {
                return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 403);
            }

            $query = EmployeeLeaveRequest::with(['approver:id,name'])
                ->where('employee_id', $profile->id)
                ->orderByDesc('created_at');

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min((int) $request->get('per_page', 15), 100);

            return EmployeeLeaveRequestResource::collection($query->paginate($perPage));
        } catch (\Exception $e) {
            Log::error('Employee leave my failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil data cuti saya.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreEmployeeLeaveRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validated();
            $canManage = $user->hasModuleAccess('kepegawaian');

            if (!$canManage) {
                $user->load(['teacherProfile', 'employeeProfile']);
                $profile = $user->teacherProfile ?? $user->employeeProfile;
                if (!$profile || (int) $profile->id !== (int) $data['employee_id']) {
                    return response()->json(['message' => 'Anda hanya dapat mengajukan cuti untuk diri sendiri.'], 403);
                }
                $data['status'] = 'pending';
            }

            $leave = $this->service->createLeave(
                $institutionId,
                $data,
                $user->id,
                $request->file('attachment')
            );

            return (new EmployeeLeaveRequestResource($leave))
                ->response()
                ->setStatusCode(201)
                ->header('X-Message', 'Pengajuan cuti berhasil disimpan.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Employee leave store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menyimpan pengajuan cuti.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function storeMy(StoreEmployeeLeaveRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['teacherProfile', 'employeeProfile']);
        $profile = $user->teacherProfile ?? $user->employeeProfile;
        if (!$profile) {
            return response()->json(['message' => 'Profil pegawai tidak ditemukan.'], 403);
        }

        $request->merge(['employee_id' => $profile->id, 'status' => 'pending']);

        return $this->store($request);
    }

    public function show(Request $request, EmployeeLeaveRequest $employee_leave_request): EmployeeLeaveRequestResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_leave_request->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee_leave_request->load(['employee:id,name,nip,nuptk,type,email', 'requester:id,name', 'approver:id,name']);

        return new EmployeeLeaveRequestResource($employee_leave_request);
    }

    public function decide(DecideEmployeeLeaveRequest $request, EmployeeLeaveRequest $employee_leave_request): EmployeeLeaveRequestResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_leave_request->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $leave = $this->service->decideLeave(
                $employee_leave_request,
                $request->validated('action'),
                $user->id,
                $request->validated('rejection_reason')
            );

            return new EmployeeLeaveRequestResource($leave);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Employee leave decide failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memproses pengajuan cuti.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function cancel(Request $request, EmployeeLeaveRequest $employee_leave_request): EmployeeLeaveRequestResource|JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['teacherProfile', 'employeeProfile']);
            $profile = $user->teacherProfile ?? $user->employeeProfile;
            $canManage = $user->hasModuleAccess('kepegawaian');

            $isOwner = $profile && (int) $profile->id === (int) $employee_leave_request->employee_id;
            if (!$canManage && !$isOwner) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_leave_request->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $leave = $this->service->cancelLeave($employee_leave_request, $user->id);

            return new EmployeeLeaveRequestResource($leave);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Employee leave cancel failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal membatalkan pengajuan cuti.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
