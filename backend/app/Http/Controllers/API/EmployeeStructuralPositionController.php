<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\EndEmployeeStructuralPositionRequest;
use App\Http\Requests\StoreEmployeeStructuralPositionRequest;
use App\Http\Requests\UpdateEmployeeStructuralPositionRequest;
use App\Http\Resources\EmployeeStructuralPositionResource;
use App\Models\EmployeeStructuralPosition;
use App\Services\KepegawaianService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EmployeeStructuralPositionController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected KepegawaianService $service
    ) {}

    public function positions(): JsonResponse
    {
        $positions = $this->service->listStructuralPositions();

        return response()->json(['data' => $positions]);
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['structural_position_id', 'employee_id', 'active_only', 'search']);
            $perPage = min((int) $request->get('per_page', 15), 100);
            $items = $this->service->listStructuralAssignments($institutionId, $filters, $perPage);

            return EmployeeStructuralPositionResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Structural position index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil data jabatan struktural.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreEmployeeStructuralPositionRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $assignment = $this->service->assignStructuralPosition(
                $institutionId,
                $request->validated(),
                $request->user()->id
            );

            return (new EmployeeStructuralPositionResource($assignment))->response()->setStatusCode(201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai, jabatan, atau SK tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Structural position store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menetapkan jabatan struktural.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, EmployeeStructuralPosition $employee_structural_position): EmployeeStructuralPositionResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_structural_position->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee_structural_position->load([
            'employee:id,name,nip,nuptk,type,email',
            'position',
            'decree:id,number,title,decree_date',
            'creator:id,name',
        ]);

        return new EmployeeStructuralPositionResource($employee_structural_position);
    }

    public function end(
        EndEmployeeStructuralPositionRequest $request,
        EmployeeStructuralPosition $employee_structural_position
    ): EmployeeStructuralPositionResource|JsonResponse {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_structural_position->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $request->validated();
            $assignment = $this->service->endStructuralAssignment(
                $employee_structural_position,
                $data['ended_at'] ?? null,
                $data['notes'] ?? null
            );

            return new EmployeeStructuralPositionResource($assignment);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Structural position end failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengakhiri jabatan struktural.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(
        UpdateEmployeeStructuralPositionRequest $request,
        EmployeeStructuralPosition $employee_structural_position
    ): EmployeeStructuralPositionResource|JsonResponse {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_structural_position->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $assignment = $this->service->updateStructuralAssignment(
                $employee_structural_position,
                $request->validated()
            );

            return new EmployeeStructuralPositionResource($assignment);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai, jabatan, atau SK tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Structural position update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui jabatan struktural.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(
        Request $request,
        EmployeeStructuralPosition $employee_structural_position
    ): JsonResponse {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_structural_position->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $this->service->deleteStructuralAssignment($employee_structural_position);

            return response()->json(['message' => 'Jabatan struktural dihapus.']);
        } catch (\Exception $e) {
            Log::error('Structural position delete failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menghapus jabatan struktural.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
