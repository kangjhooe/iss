<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeDecreeRequest;
use App\Http\Requests\UpdateEmployeeDecreeRequest;
use App\Http\Resources\EmployeeDecreeResource;
use App\Models\EmployeeDecree;
use App\Services\KepegawaianService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDecreeController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected KepegawaianService $service
    ) {}

    public function meta(): JsonResponse
    {
        return response()->json([
            'decree_types' => EmployeeDecree::TYPES,
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $filters = $request->only(['decree_type', 'employee_id', 'search']);
            $perPage = min((int) $request->get('per_page', 15), 100);
            $items = $this->service->listDecrees($institutionId, $filters, $perPage);

            return EmployeeDecreeResource::collection($items);
        } catch (\Exception $e) {
            Log::error('Employee decree index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil data SK.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreEmployeeDecreeRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $decree = $this->service->createDecree(
                $institutionId,
                $request->validated(),
                $request->user()->id,
                $request->file('file')
            );

            return (new EmployeeDecreeResource($decree))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Employee decree store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menyimpan SK.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, EmployeeDecree $employee_decree): EmployeeDecreeResource|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_decree->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee_decree->load(['employee:id,name,nip,nuptk,type,email', 'creator:id,name']);

        return new EmployeeDecreeResource($employee_decree);
    }

    public function update(UpdateEmployeeDecreeRequest $request, EmployeeDecree $employee_decree): EmployeeDecreeResource|JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_decree->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $decree = $this->service->updateDecree(
                $employee_decree,
                $request->validated(),
                $request->file('file')
            );

            return new EmployeeDecreeResource($decree);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Employee decree update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui SK.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, EmployeeDecree $employee_decree): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_decree->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $this->service->deleteDecree($employee_decree);

            return response()->json(['message' => 'SK berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('Employee decree destroy failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menghapus SK.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function download(Request $request, EmployeeDecree $employee_decree): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee_decree->institution_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$employee_decree->file_path || !Storage::disk('public')->exists($employee_decree->file_path)) {
            return response()->json(['message' => 'File SK tidak ditemukan.'], 404);
        }

        return Storage::disk('public')->download(
            $employee_decree->file_path,
            $employee_decree->file_name ?: 'sk.pdf'
        );
    }
}
