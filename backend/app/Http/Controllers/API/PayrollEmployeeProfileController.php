<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesPayrollInstitution;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayrollEmployeeProfileResource;
use App\Models\Employee;
use App\Models\PayrollEmployeeComponent;
use App\Models\PayrollEmployeeProfile;
use App\Services\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PayrollEmployeeProfileController extends Controller
{
    use ResolvesPayrollInstitution;

    public function __construct(protected PayrollService $payrollService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $this->payrollService->ensureDefaultComponents($institutionId);

            $query = PayrollEmployeeProfile::forInstitution($institutionId)
                ->with(['employee:id,institution_id,nip,name,type,employment_status,status'])
                ->orderByDesc('updated_at');

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->whereHas('employee', fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%"));
            }

            $perPage = min(100, max(10, (int) $request->get('per_page', 20)));
            $paginated = $query->paginate($perPage);

            $employeeIds = $paginated->getCollection()->pluck('employee_id');
            $components = PayrollEmployeeComponent::query()
                ->where('institution_id', $institutionId)
                ->whereIn('employee_id', $employeeIds)
                ->with('component:id,code,name,type')
                ->get()
                ->groupBy('employee_id');

            $paginated->getCollection()->transform(function (PayrollEmployeeProfile $profile) use ($components) {
                $profile->setRelation('employee_components', $components->get($profile->employee_id, collect()));

                return $profile;
            });

            return PayrollEmployeeProfileResource::collection($paginated);
        } catch (\Exception $e) {
            Log::error('PayrollEmployeeProfile index failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat profil gaji pegawai.'], 500);
        }
    }

    public function employeesLite(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $employees = Employee::query()
                ->where('status', 'Aktif')
                ->where(function ($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)
                        ->orWhereHas('assignments', fn ($a) => $a->where('institution_id', $institutionId)->where('status', 'approved'));
                })
                ->orderBy('name')
                ->get(['id', 'nip', 'name', 'type', 'employment_status']);

            $profileIds = PayrollEmployeeProfile::forInstitution($institutionId)
                ->pluck('employee_id')
                ->flip();

            $rows = $employees->map(fn (Employee $e) => [
                'id' => $e->id,
                'nip' => $e->nip,
                'name' => $e->name,
                'type' => $e->type,
                'employment_status' => $e->employment_status,
                'has_profile' => $profileIds->has($e->id),
            ]);

            return response()->json(['data' => $rows]);
        } catch (\Exception $e) {
            Log::error('PayrollEmployeeProfile employeesLite failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memuat daftar pegawai.'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $institutionId = $this->payrollInstitutionId($request);
            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $data = $request->validate([
                'employee_id' => ['required', 'integer'],
                'base_salary' => ['required', 'numeric', 'min:0'],
                'payment_method' => ['nullable', 'string', 'max:20'],
                'bank_name' => ['nullable', 'string', 'max:80'],
                'bank_account' => ['nullable', 'string', 'max:40'],
                'effective_from' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
                'components' => ['nullable', 'array'],
                'components.*.component_id' => ['required_with:components', 'integer'],
                'components.*.amount' => ['nullable', 'numeric', 'min:0'],
                'components.*.is_active' => ['nullable', 'boolean'],
            ]);

            $profile = $this->payrollService->upsertEmployeeProfile($institutionId, $data);

            return (new PayrollEmployeeProfileResource($profile))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollEmployeeProfile store failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal menyimpan profil gaji.'], 500);
        }
    }

    public function update(Request $request, PayrollEmployeeProfile $profile): PayrollEmployeeProfileResource|JsonResponse
    {
        try {
            if ($denied = $this->denyPayrollForeign($request, (int) $profile->institution_id)) {
                return $denied;
            }

            $data = $request->validate([
                'base_salary' => ['sometimes', 'numeric', 'min:0'],
                'payment_method' => ['nullable', 'string', 'max:20'],
                'bank_name' => ['nullable', 'string', 'max:80'],
                'bank_account' => ['nullable', 'string', 'max:40'],
                'effective_from' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
                'components' => ['nullable', 'array'],
                'components.*.component_id' => ['required_with:components', 'integer'],
                'components.*.amount' => ['nullable', 'numeric', 'min:0'],
                'components.*.is_active' => ['nullable', 'boolean'],
            ]);

            $data['employee_id'] = $profile->employee_id;
            $profile = $this->payrollService->upsertEmployeeProfile((int) $profile->institution_id, $data);

            return new PayrollEmployeeProfileResource($profile);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('PayrollEmployeeProfile update failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Gagal memperbarui profil gaji.'], 500);
        }
    }
}
