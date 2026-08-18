<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\KepegawaianService;
use App\Support\InstitutionContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeCareerHistoryController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected KepegawaianService $service
    ) {}

    public function show(Request $request, int $employeeId): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $employee = Employee::with('institution:id,name')
                ->where('institution_id', $institutionId)
                ->findOrFail($employeeId);

            $user = $request->user();
            if (!$user->isSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $employee->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return response()->json([
                'data' => [
                    'employee' => [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'nip' => $employee->nip,
                        'nuptk' => $employee->nuptk,
                        'type' => $employee->type,
                        'employment_status' => $employee->employment_status,
                        'status' => $employee->status,
                        'join_date' => $employee->join_date?->format('Y-m-d'),
                        'institution' => $employee->institution
                            ? ['id' => $employee->institution->id, 'name' => $employee->institution->name]
                            : null,
                    ],
                    'timeline' => $this->service->careerHistory($employee),
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('Career history failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal mengambil riwayat kepegawaian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
