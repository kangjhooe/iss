<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminResetEmployeePasswordRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\User;
use App\Services\EmployeeService;
use App\Services\StructuralDutySync;
use App\Support\InstitutionContext;
use App\Support\ReportAccess;
use App\Support\TeacherAccess;
use App\Support\VocationalAccess;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EmployeeController extends Controller
{
    public function __construct(
        protected StructuralDutySync $structuralDutySync,
        protected \App\Services\EmployeeService $employeeService
    ) {}

    /**
     * Display a listing of employees (guru/staff).
     *
     * @OA\Get(
     *     path="/api/v1/employee",
     *     summary="Daftar pegawai (guru/staff)",
     *     tags={"Teacher"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string"), description="Cari nama/NIP/NUPTK"),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string"), description="Filter status (Aktif/Pensiun/dll)"),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string"), description="Filter tipe (Guru/Staff/dll)"),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer"), description="Jumlah per halaman (max 100)"),
     *
     *     @OA\Response(response=200, description="Berhasil",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="nip", type="string"),
     *                 @OA\Property(property="type", type="string"),
     *                 @OA\Property(property="status", type="string")
     *             ))
     *         )
     *     ),
     *
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Employee::query();
            $user = $request->user();
            $institutionId = null;

            if (filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN)) {
                $query->onlyTrashed();
            } elseif (filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN)) {
                $query->withTrashed();
            }

            // Filter berdasarkan institusi aktif (induk / non-induk)
            if (! $user->isAdminOrSuperAdmin()) {
                $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            if ($institutionId) {
                $query->where(function ($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)
                        ->orWhereHas('assignments', function ($assignmentQuery) use ($institutionId) {
                            $assignmentQuery->where('institution_id', $institutionId)
                                ->where('status', 'approved');
                        });
                });
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nip', 'like', '%'.$search.'%')
                        ->orWhere('nuptk', 'like', '%'.$search.'%');
                });
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('employment_status')) {
                $query->where('employment_status', $request->employment_status);
            }

            $perPage = min($request->get('per_page', 15), 500);
            $relations = ['institution:id,name'];
            if ($institutionId) {
                $relations['assignments'] = function ($assignmentQuery) use ($institutionId) {
                    $assignmentQuery->where('institution_id', $institutionId)
                        ->where('status', 'approved');
                };
            }

            $employees = $query->select($this->employeeIndexColumns())
                ->with($relations)
                ->orderBy('type')
                ->orderBy('name')
                ->paginate($perPage);

            if ($institutionId) {
                $request->attributes->set('current_institution_id', $institutionId);
            }

            return EmployeeResource::collection($employees);
        } catch (\Exception $e) {
            Log::error('Failed to list employees', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Export daftar pegawai (baris lengkap untuk Excel client-side).
     */
    public function export(Request $request)
    {
        try {
            $query = Employee::query();
            $user = $request->user();
            $institutionId = null;

            if (filter_var($request->get('only_trashed'), FILTER_VALIDATE_BOOLEAN)) {
                $query->onlyTrashed();
            } elseif (filter_var($request->get('with_trashed'), FILTER_VALIDATE_BOOLEAN)) {
                $query->withTrashed();
            }

            if (! $user->isAdminOrSuperAdmin()) {
                $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
            } elseif ($request->has('institution_id')) {
                $institutionId = $request->institution_id;
            }

            if ($institutionId) {
                $query->where(function ($q) use ($institutionId) {
                    $q->where('institution_id', $institutionId)
                        ->orWhereHas('assignments', function ($assignmentQuery) use ($institutionId) {
                            $assignmentQuery->where('institution_id', $institutionId)
                                ->where('status', 'approved');
                        });
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nip', 'like', '%'.$search.'%')
                        ->orWhere('nuptk', 'like', '%'.$search.'%');
                });
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('employment_status')) {
                $query->where('employment_status', $request->employment_status);
            }

            $relations = ['institution:id,name,npsn'];
            if ($institutionId) {
                $relations['assignments'] = function ($assignmentQuery) use ($institutionId) {
                    $assignmentQuery->where('institution_id', $institutionId)
                        ->where('status', 'approved');
                };
            }

            $limit = (int) $request->get('limit', 5000);
            $employees = $query
                ->with($relations)
                ->orderBy('name')
                ->limit(max(1, min($limit, 20000)))
                ->get();

            if ($institutionId) {
                $request->attributes->set('current_institution_id', $institutionId);
            }

            return EmployeeResource::collection($employees);
        } catch (\Exception $e) {
            Log::error('Failed to export employees', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengekspor data pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cetak daftar guru/pegawai sebagai PDF (kop + tanda tangan, inline stream untuk preview).
     */
    public function exportPdf(Request $request): Response|\Illuminate\Http\JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = null;

            if (! $user->isAdminOrSuperAdmin()) {
                $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
            } elseif ($request->filled('institution_id')) {
                $institutionId = (int) $request->institution_id;
            } else {
                $institutionId = InstitutionContext::resolveForUser($user, $request, null);
            }

            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $institution = Institution::find($institutionId);
            if (! $institution) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 404);
            }

            $query = Employee::query();
            $query->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)
                    ->orWhereHas('assignments', function ($assignmentQuery) use ($institutionId) {
                        $assignmentQuery->where('institution_id', $institutionId)
                            ->where('status', 'approved');
                    });
            });

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('nip', 'like', '%'.$search.'%')
                        ->orWhere('nuptk', 'like', '%'.$search.'%');
                });
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('employment_status')) {
                $query->where('employment_status', $request->employment_status);
            }

            $employees = $query
                ->select([
                    'id', 'institution_id', 'type', 'nip', 'nuptk', 'name', 'gender',
                    'subject', 'status', 'employment_status', 'created_at',
                ])
                ->orderBy('name')
                ->limit(2000)
                ->get();

            $filterParts = [];
            if ($request->filled('search')) {
                $filterParts[] = 'Pencarian: '.$request->search;
            }
            if ($request->filled('type')) {
                $filterParts[] = 'Tipe: '.$request->type;
            }
            if ($request->filled('status')) {
                $filterParts[] = 'Status: '.$request->status;
            }
            if ($request->filled('employment_status')) {
                $filterParts[] = 'Kepegawaian: '.$request->employment_status;
            }

            $printedAt = now()->locale('id')->isoFormat('D MMMM YYYY HH:mm');
            $pdf = DomPDF::loadView('employee.print', [
                'institution' => $institution,
                'institutionId' => $institutionId,
                'employees' => $employees,
                'filter_label' => $filterParts ? implode(' · ', $filterParts) : null,
                'printed_at' => $printedAt,
            ])->setPaper('a4', 'landscape');

            $filename = 'Data_Guru_'.date('Y-m-d_His').'.pdf';

            return $pdf->stream($filename, ['Attachment' => false]);
        } catch (\Exception $e) {
            Log::error('Failed to export employees to PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mencetak data guru',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Search employee by NIK (for non-induk requests).
     * Cross-school lookup is intentional; response is limited to confirmation fields.
     */
    public function searchByNik(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|size:16|regex:/^[0-9]{16}$/',
        ]);

        $user = $request->user();
        if (! $user->isInstitutionAdmin() && ! $user->isAdminOrSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = Employee::with('institution:id,name')->where('nik', $request->nik)->first();

        if (! $employee) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $employee->id,
                'nik' => $employee->nik,
                'name' => $employee->name,
                'institution' => $employee->institution ? [
                    'id' => $employee->institution->id,
                    'name' => $employee->institution->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Store a newly created employee (guru/staff).
     *
     * @OA\Post(
     *     path="/api/v1/employee",
     *     summary="Tambah pegawai (guru/staff)",
     *     tags={"Teacher"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"nik","type","name","gender"},
     *
     *             @OA\Property(property="institution_id", type="integer", description="ID institusi (untuk super admin)"),
     *             @OA\Property(property="nik", type="string", example="1234567890123456", description="NIK 16 digit"),
     *             @OA\Property(property="type", type="string", enum={"Guru","Staff","Tenaga Administrasi", "Tenaga Kebersihan","Tenaga Keamanan","Lainnya"}, example="Guru"),
     *             @OA\Property(property="name", type="string", example="Budi Santoso"),
     *             @OA\Property(property="gender", type="string", enum={"L","P"}, example="L"),
     *             @OA\Property(property="nip", type="string"),
     *             @OA\Property(property="nuptk", type="string"),
     *             @OA\Property(property="status", type="string", example="Aktif")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Pegawai berhasil ditambahkan",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Pegawai berhasil ditambahkan"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin()
                ? $request->institution_id
                : $request->user()->institution_id;

            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validated();
            $validated['institution_id'] = $institutionId;

            // Extract educations if provided
            $educations = $validated['educations'] ?? [];
            unset($validated['educations']);

            $additionalDutyIds = VocationalAccess::filterDutyIdsForInstitution(
                $institutionId,
                $validated['additional_duty_ids'] ?? []
            );
            unset($validated['additional_duty_ids']);
            $programKeahlianIds = $validated['program_keahlian_ids'] ?? [];
            unset($validated['program_keahlian_ids']);
            if (! VocationalAccess::isVocationalInstitution($institutionId)) {
                $programKeahlianIds = [];
            }

            $permissionKeys = $validated['permission_keys'] ?? null;
            if (is_array($permissionKeys)) {
                $permissionKeys = VocationalAccess::filterPermissionKeysForInstitution($institutionId, $permissionKeys);
            }
            $userRole = $validated['user_role'] ?? null;
            unset($validated['permission_keys'], $validated['user_role']);
            if (! $request->user()->isAdminOrSuperAdmin() && ! $request->user()->isInstitutionAdmin()) {
                $permissionKeys = null;
            }
            if (! empty($validated['email']) && $userRole === null) {
                $userRole = $validated['type'] === 'Guru' ? 'teacher' : 'staff';
            }

            $employee = Employee::create($validated);

            // Save educations
            if (! empty($educations)) {
                foreach ($educations as $index => $education) {
                    if (! empty($education['level'])) {
                        $employee->educations()->create([
                            'level' => $education['level'],
                            'school_name' => $education['school_name'] ?? null,
                            'major' => $education['major'] ?? null,
                            'graduation_year' => $education['graduation_year'] ?? null,
                            'certificate_number' => $education['certificate_number'] ?? null,
                            'city' => $education['city'] ?? null,
                            'notes' => $education['notes'] ?? null,
                            'order' => $index,
                        ]);
                    }
                }
            }

            // Sync additional duties (tugas tambahan); jabatan struktural ikut dari Kepegawaian.
            $additionalDutyIds = $this->structuralDutySync->applyDutyIds(
                $employee,
                $additionalDutyIds,
                (int) $request->user()->id
            );
            $this->syncKaprogPrograms($employee, $additionalDutyIds, $programKeahlianIds);

            $effectivePermissionKeys = $this->getEffectivePermissionKeys($employee, $permissionKeys);
            $accountResult = $this->ensureEmployeeUserAccount($employee, null, $effectivePermissionKeys, $userRole);

            Log::info('Employee created', [
                'employee_id' => $employee->id,
                'institution_id' => $institutionId,
                'type' => $employee->type,
                'user_id' => $request->user()->id,
            ]);

            $response = [
                'message' => 'Pegawai berhasil ditambahkan',
                'data' => new EmployeeResource($employee->load(['institution', 'educations', 'documents', 'userAccount.permissions', 'additionalDuties', 'programKeahlians'])),
            ];

            if (! empty($accountResult['user_created'])) {
                $response['user_created'] = true;
                $response['generated_password'] = $accountResult['generated_password'];
            }

            if (! empty($accountResult['user_updated'])) {
                $response['user_updated'] = true;
            }

            if (! empty($accountResult['user_conflict'])) {
                $response['user_conflict'] = $accountResult['user_conflict'];
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create employee', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menambahkan pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Display the specified employee (guru/staff).
     *
     * @OA\Get(
     *     path="/api/v1/employee/{id}",
     *     summary="Detail pegawai",
     *     tags={"Teacher"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Berhasil", @OA\JsonContent(@OA\Property(property="data", type="object"))),
     *     @OA\Response(response=404, description="Pegawai tidak ditemukan")
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $previousEmail = $employee->email;
            $user = $request->user();
            $currentInstitutionId = \App\Support\InstitutionContext::resolveForUser(
                $user,
                $request,
                $request->get('institution_id')
            );

            // Jika bukan admin/super admin, hanya bisa melihat pegawai dari institusi sendiri atau non-induk yang disetujui
            if (! $user->isAdminOrSuperAdmin() && $currentInstitutionId != $employee->institution_id) {
                $hasApprovedAssignment = $employee->assignments()
                    ->where('institution_id', $currentInstitutionId)
                    ->where('status', 'approved')
                    ->exists();

                if (! $hasApprovedAssignment) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $relations = ['institution', 'educations', 'documents', 'userAccount.permissions', 'additionalDuties', 'programKeahlians'];

            if ($user->isAdminOrSuperAdmin() || $employee->institution_id == $currentInstitutionId) {
                $relations[] = 'assignments.institution';
                $relations[] = 'assignments.requester';
                $relations[] = 'assignments.approver';
            } else {
                $relations['assignments'] = function ($assignmentQuery) use ($currentInstitutionId) {
                    $assignmentQuery->where('institution_id', $currentInstitutionId)
                        ->where('status', 'approved')
                        ->with('institution:id,name');
                };
            }

            $employee->load($relations);

            if ($currentInstitutionId) {
                $request->attributes->set('current_institution_id', $currentInstitutionId);
            }

            return new EmployeeResource($employee);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to get employee', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update the specified employee (guru/staff).
     *
     * @OA\Put(
     *     path="/api/v1/employee/{id}",
     *     summary="Perbarui pegawai",
     *     tags={"Teacher"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\RequestBody(@OA\JsonContent(
     *
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="status", type="string"),
     *         @OA\Property(property="nip", type="string"),
     *         @OA\Property(property="nuptk", type="string")
     *     )),
     *
     *     @OA\Response(response=200, description="Pegawai berhasil diperbarui"),
     *     @OA\Response(response=404, description="Pegawai tidak ditemukan")
     * )
     */
    public function update(UpdateEmployeeRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $previousEmail = $employee->email;

            // Jika bukan admin, hanya bisa update pegawai dari institusi sendiri
            if (! $request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validated();

            // Extract educations if provided
            $educations = $validated['educations'] ?? null;
            unset($validated['educations']);

            $institutionId = (int) $employee->institution_id;
            $additionalDutyIds = array_key_exists('additional_duty_ids', $validated) ? $validated['additional_duty_ids'] : null;
            if (is_array($additionalDutyIds)) {
                $additionalDutyIds = VocationalAccess::filterDutyIdsForInstitution($institutionId, $additionalDutyIds);
            }
            unset($validated['additional_duty_ids']);
            $programKeahlianIds = array_key_exists('program_keahlian_ids', $validated) ? $validated['program_keahlian_ids'] : null;
            unset($validated['program_keahlian_ids']);
            if (is_array($programKeahlianIds) && ! VocationalAccess::isVocationalInstitution($institutionId)) {
                $programKeahlianIds = [];
            }

            $permissionKeys = null;
            $userRole = null;
            if (array_key_exists('permission_keys', $validated)) {
                $permissionKeys = VocationalAccess::filterPermissionKeysForInstitution(
                    $institutionId,
                    $validated['permission_keys'] ?? []
                );
            }
            if (array_key_exists('user_role', $validated)) {
                $userRole = $validated['user_role'];
            }
            unset($validated['permission_keys'], $validated['user_role']);
            if (! $request->user()->isAdminOrSuperAdmin() && ! $request->user()->isInstitutionAdmin()) {
                $permissionKeys = null;
            }
            if (! empty($validated['email'] ?? $employee->email) && $userRole === null) {
                $userRole = $employee->type === 'Guru' ? 'teacher' : 'staff';
            }

            $employee->update($validated);

            // Update educations if provided
            if ($educations !== null) {
                // Delete existing educations
                $employee->educations()->delete();

                // Create new educations
                foreach ($educations as $index => $education) {
                    if (! empty($education['level'])) {
                        $employee->educations()->create([
                            'level' => $education['level'],
                            'school_name' => $education['school_name'] ?? null,
                            'major' => $education['major'] ?? null,
                            'graduation_year' => $education['graduation_year'] ?? null,
                            'certificate_number' => $education['certificate_number'] ?? null,
                            'city' => $education['city'] ?? null,
                            'notes' => $education['notes'] ?? null,
                            'order' => $index,
                        ]);
                    }
                }
            }

            // Sync additional duties (tugas tambahan) if provided
            if ($additionalDutyIds !== null) {
                $additionalDutyIds = $this->structuralDutySync->applyDutyIds(
                    $employee,
                    $additionalDutyIds,
                    (int) $request->user()->id
                );
            }
            if ($additionalDutyIds !== null || $programKeahlianIds !== null) {
                $dutyIdsForKaprog = $additionalDutyIds !== null
                    ? $additionalDutyIds
                    : $employee->additionalDuties()->pluck('additional_duties.id')->all();
                $this->syncKaprogPrograms(
                    $employee,
                    $dutyIdsForKaprog,
                    $programKeahlianIds ?? $employee->programKeahlians()->pluck('program_keahlian.id')->all()
                );
            }

            // When only additional_duty_ids sent, keep current user permissions as manual base
            $manualKeys = $permissionKeys;
            if ($manualKeys === null && $employee->userAccount) {
                $manualKeys = $employee->userAccount->permissions()->pluck('key')->toArray();
            }
            $effectivePermissionKeys = ($additionalDutyIds !== null || $permissionKeys !== null)
                ? $this->getEffectivePermissionKeys($employee, $manualKeys ?? [])
                : null;
            $accountResult = $this->ensureEmployeeUserAccount($employee, $previousEmail, $effectivePermissionKeys, $userRole);

            Log::info('Employee updated', [
                'employee_id' => $employee->id,
                'user_id' => $request->user()->id,
            ]);

            $response = [
                'message' => 'Pegawai berhasil diperbarui',
                'data' => new EmployeeResource($employee->load(['institution', 'educations', 'documents', 'userAccount.permissions', 'additionalDuties', 'programKeahlians'])),
            ];

            if (! empty($accountResult['user_created'])) {
                $response['user_created'] = true;
                $response['generated_password'] = $accountResult['generated_password'];
            }

            if (! empty($accountResult['user_updated'])) {
                $response['user_updated'] = true;
            }

            if (! empty($accountResult['user_conflict'])) {
                $response['user_conflict'] = $accountResult['user_conflict'];
            }

            return response()->json($response);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update employee', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reset employee user password (by admin/institution_admin).
     */
    public function resetPasswordByAdmin(AdminResetEmployeePasswordRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $user = $request->user();

            if (! $user->isAdminOrSuperAdmin() && ! $user->isInstitutionAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $user->isAdminOrSuperAdmin() && $user->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $employee->email || ! $employee->hasUserAccount()) {
                return response()->json([
                    'message' => 'Pegawai ini belum memiliki akun login.',
                ], 422);
            }

            $accountUser = User::where('email', $employee->email)->first();
            if (! $accountUser) {
                return response()->json([
                    'message' => 'Akun login tidak ditemukan.',
                ], 404);
            }

            if (! in_array($accountUser->role, ['teacher', 'staff'], true)) {
                return response()->json([
                    'message' => 'Hanya dapat mereset sandi akun pegawai (guru/staff).',
                ], 422);
            }

            $accountUser->update([
                'password' => Hash::make($request->validated('password')),
            ]);
            $accountUser->resetFailedLoginAttempts();

            Log::info('Employee password reset by admin', [
                'employee_id' => $employee->id,
                'user_id' => $accountUser->id,
                'reset_by' => $user->id,
            ]);

            return response()->json([
                'message' => 'Sandi berhasil direset. Beri tahu pegawai sandi baru secara aman dan sarankan ganti sandi setelah login.',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Admin reset employee password failed', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat reset sandi',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Remove the specified employee (soft delete).
     *
     * @OA\Delete(
     *     path="/api/v1/employee/{id}",
     *     summary="Hapus pegawai",
     *     tags={"Teacher"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *
     *     @OA\Response(response=200, description="Pegawai berhasil dihapus"),
     *     @OA\Response(response=404, description="Pegawai tidak ditemukan")
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            // Jika bukan admin, hanya bisa hapus pegawai dari institusi sendiri
            if (! $request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $employee->delete();

            Log::info('Employee deleted', [
                'employee_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Pegawai berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete employee', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Restore a soft-deleted employee.
     */
    public function restore(Request $request, $id)
    {
        try {
            $employee = Employee::withTrashed()->findOrFail($id);

            if (! $request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($employee->trashed()) {
                $employee->restore();
            }

            return response()->json([
                'message' => 'Pegawai berhasil dipulihkan',
                'data' => new EmployeeResource($employee->fresh(['institution', 'educations', 'documents', 'userAccount'])),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to restore employee', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memulihkan pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Permanently delete an employee from the trash.
     */
    public function forceDestroy(Request $request, $id)
    {
        try {
            $employee = Employee::withTrashed()->findOrFail($id);

            if (! $request->user()->isAdminOrSuperAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (! $employee->trashed()) {
                return response()->json([
                    'message' => 'Hanya data di kotak sampah yang dapat dihapus permanen.',
                ], 422);
            }

            $filePaths = $employee->documents()->pluck('file_path')->filter()->all();
            $employeeId = $employee->id;
            $nik = $employee->nik;
            $photoPath = $employee->photo_path;

            DB::transaction(function () use ($employee, $request) {
                $this->releaseEmployeeUserAccount($employee, $request->user());
                $employee->forceDelete();
            });

            foreach ($filePaths as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
            Storage::disk('public')->deleteDirectory('employee_documents/'.$employeeId);
            if ($photoPath && Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
            Storage::disk('public')->deleteDirectory('employee_photos/'.$employeeId);

            Log::info('Employee permanently deleted', [
                'employee_id' => $employeeId,
                'nik' => $nik,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Pegawai dihapus secara permanen',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to permanently delete employee', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus permanen pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Hapus atau nonaktifkan akun login guru/staf agar email/NIK bisa dipakai lagi.
     */
    protected function releaseEmployeeUserAccount(Employee $employee, User $actor): void
    {
        if (empty($employee->email)) {
            return;
        }

        $user = User::where('email', $employee->email)->first();
        if (! $user) {
            return;
        }

        if ((int) $user->id === (int) $actor->id) {
            throw new \InvalidArgumentException('Tidak dapat menghapus permanen data yang terhubung dengan akun Anda.');
        }

        if (! in_array($user->role, ['teacher', 'staff'], true)) {
            return;
        }

        $user->tokens()->delete();

        try {
            $user->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            $user->forceFill([
                'is_active' => false,
                'login_nik' => null,
                'email' => 'deleted.'.$user->id.'.'.$user->email,
            ])->save();

            Log::warning('Employee login account could not be deleted; deactivated instead', [
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload document for employee.
     */
    public function uploadDocument(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            // Jika bukan admin, hanya bisa upload dokumen pegawai dari institusi sendiri
            if (! $request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check document count limit (max 20)
            $documentCount = $employee->documents()->count();
            if ($documentCount >= 20) {
                return response()->json([
                    'message' => 'Maksimal 20 file dokumen per pegawai',
                ], 400);
            }

            // Use standardized file upload validation
            $rules = array_merge(
                \App\Helpers\FileUploadRules::employeeDocument(),
                [
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                ]
            );
            $messages = \App\Helpers\FileUploadRules::messages(
                \App\Helpers\FileUploadRules::TYPE_PDF_ONLY,
                \App\Helpers\FileUploadRules::SIZE_SMALL,
                'file',
                false
            );

            $request->validate($rules, $messages);

            $file = $request->file('file');
            // Sanitize file name to prevent path traversal
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $fileName = time().'_'.$safeName.'.'.$extension;
            $filePath = $file->storeAs('employee_documents/'.$employee->id, $fileName, 'public');

            $document = $employee->documents()->create([
                'name' => $request->name,
                'file_path' => $filePath,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $request->description,
            ]);

            Log::info('Employee document uploaded', [
                'employee_id' => $employee->id,
                'document_id' => $document->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Dokumen berhasil diupload',
                'data' => $document,
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai tidak ditemukan',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to upload document', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengupload dokumen',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Delete document for employee.
     */
    public function deleteDocument(Request $request, $id, $documentId)
    {
        try {
            $employee = Employee::findOrFail($id);

            // Jika bukan admin, hanya bisa hapus dokumen pegawai dari institusi sendiri
            if (! $request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $document = $employee->documents()->findOrFail($documentId);

            // Delete file from storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            Log::info('Employee document deleted', [
                'employee_id' => $employee->id,
                'document_id' => $documentId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Dokumen berhasil dihapus',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai atau dokumen tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete document', [
                'employee_id' => $id,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus dokumen',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Download document for employee.
     */
    public function downloadDocument(Request $request, $id, $documentId)
    {
        try {
            $employee = Employee::findOrFail($id);

            // Jika bukan admin, hanya bisa download dokumen pegawai dari institusi sendiri
            if (! $request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $document = $employee->documents()->findOrFail($documentId);

            if (! Storage::disk('public')->exists($document->file_path)) {
                return response()->json([
                    'message' => 'File tidak ditemukan',
                ], 404);
            }

            return Storage::disk('public')->download($document->file_path, $document->file_name);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pegawai atau dokumen tidak ditemukan',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Failed to download document', [
                'employee_id' => $id,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mendownload dokumen',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function uploadPhoto(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            if (! $this->userCanManageEmployee($request, $employee)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $request->validate(
                \App\Helpers\FileUploadRules::employeePhoto(true),
                \App\Helpers\FileUploadRules::profilePhotoMessages()
            );

            $updated = $this->employeeService->storePhoto($employee, $request->file('photo'));

            return response()->json([
                'message' => 'Foto pegawai berhasil diunggah',
                'data' => new EmployeeResource($updated->loadMissing('institution')),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to upload employee photo', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengunggah foto',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deletePhoto(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            if (! $this->userCanManageEmployee($request, $employee)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $updated = $this->employeeService->deletePhoto($employee);

            return response()->json([
                'message' => 'Foto pegawai dihapus',
                'data' => new EmployeeResource($updated->loadMissing('institution')),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete employee photo', [
                'employee_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menghapus foto',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function userCanManageEmployee(Request $request, Employee $employee): bool
    {
        $user = $request->user();
        if ($user->isAdminOrSuperAdmin()) {
            return true;
        }

        return (int) $user->institution_id === (int) $employee->institution_id;
    }

    /**
     * Import employees from Excel data.
     */
    public function import(Request $request)
    {
        try {
            $employeesData = $request->input('employees', []);

            if (empty($employeesData) || ! is_array($employeesData)) {
                return response()->json([
                    'message' => 'Data pegawai tidak valid',
                ], 400);
            }

            $institutionId = $request->user()->isAdmin()
                ? $request->input('institution_id')
                : $request->user()->institution_id;

            if (! $institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $createdAccounts = [];
            $accountConflicts = [];

            foreach ($employeesData as $index => $employeeData) {
                try {
                    $rowNumber = $index + 1;
                    $name = trim((string) ($employeeData['name'] ?? ''));
                    $nik = preg_replace('/\D+/', '', (string) ($employeeData['nik'] ?? ''));
                    $typeRaw = trim((string) ($employeeData['type'] ?? ''));
                    $genderRaw = strtoupper(trim((string) ($employeeData['gender'] ?? '')));
                    $birthPlace = trim((string) ($employeeData['birth_place'] ?? ''));
                    $birthDate = trim((string) ($employeeData['birth_date'] ?? ''));
                    $email = trim((string) ($employeeData['email'] ?? ''));

                    $allowedTypes = ['Guru', 'Staff', 'Tenaga Administrasi', 'Tenaga Kebersihan', 'Tenaga Keamanan', 'Lainnya'];
                    $matchedType = null;
                    foreach ($allowedTypes as $allowedType) {
                        if (strcasecmp($allowedType, $typeRaw) === 0) {
                            $matchedType = $allowedType;
                            break;
                        }
                    }

                    if ($name === '') {
                        $errors[] = 'Baris '.$rowNumber.': Nama Lengkap wajib diisi';
                        $errorCount++;
                        continue;
                    }
                    if ($nik === '') {
                        $errors[] = 'Baris '.$rowNumber.': NIK wajib diisi';
                        $errorCount++;
                        continue;
                    }
                    if (! preg_match('/^[0-9]{16}$/', $nik)) {
                        $errors[] = 'Baris '.$rowNumber.': Format NIK tidak valid (harus 16 digit)';
                        $errorCount++;
                        continue;
                    }
                    if ($typeRaw === '') {
                        $errors[] = 'Baris '.$rowNumber.': Tipe Pegawai wajib diisi';
                        $errorCount++;
                        continue;
                    }
                    if ($matchedType === null) {
                        $errors[] = 'Baris '.$rowNumber.': Tipe Pegawai tidak valid (Guru, Staff, Tenaga Administrasi, Tenaga Kebersihan, Tenaga Keamanan, atau Lainnya)';
                        $errorCount++;
                        continue;
                    }
                    if (! in_array($genderRaw, ['L', 'P'], true)) {
                        $errors[] = 'Baris '.$rowNumber.': Jenis kelamin wajib diisi (L atau P)';
                        $errorCount++;
                        continue;
                    }
                    if ($birthPlace === '') {
                        $errors[] = 'Baris '.$rowNumber.': Tempat Lahir wajib diisi';
                        $errorCount++;
                        continue;
                    }
                    if ($birthDate === '' || strtotime($birthDate) === false) {
                        $errors[] = 'Baris '.$rowNumber.': Tanggal Lahir wajib diisi (format YYYY-MM-DD)';
                        $errorCount++;
                        continue;
                    }
                    if ($matchedType === 'Guru' && $email === '') {
                        $errors[] = 'Baris '.$rowNumber.': Email wajib diisi untuk guru';
                        $errorCount++;
                        continue;
                    }
                    if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = 'Baris '.$rowNumber.': Format email tidak valid';
                        $errorCount++;
                        continue;
                    }

                    $employeeData['name'] = $name;
                    $employeeData['nik'] = $nik;
                    $employeeData['type'] = $matchedType;
                    $employeeData['gender'] = $genderRaw;
                    $employeeData['birth_place'] = $birthPlace;
                    $employeeData['birth_date'] = date('Y-m-d', strtotime($birthDate));
                    $employeeData['email'] = $email !== '' ? $email : null;

                    $payload = array_intersect_key($employeeData, array_flip([
                        'type', 'nik', 'nip', 'nuptk', 'name', 'gender', 'birth_place', 'birth_date',
                        'address', 'village', 'sub_district', 'district', 'province', 'postal_code',
                        'phone', 'email', 'religion', 'employment_status', 'education_level',
                        'major', 'subject', 'status', 'join_date', 'notes', 'certification_status',
                        'certification_date', 'teacher_registration_number', 'certification_number',
                        'certification_issuing_authority',
                    ]));

                    foreach (['address', 'village', 'sub_district', 'district', 'province', 'postal_code'] as $field) {
                        if (! array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                            unset($payload[$field]);
                        }
                    }
                    if (
                        array_key_exists('village', $payload)
                        || array_key_exists('sub_district', $payload)
                        || array_key_exists('district', $payload)
                        || array_key_exists('province', $payload)
                    ) {
                        $payload['wilayah_province_code'] = null;
                        $payload['wilayah_regency_code'] = null;
                        $payload['wilayah_district_code'] = null;
                        $payload['wilayah_village_code'] = null;
                    }

                    // Cek apakah pegawai sudah ada berdasarkan NIK (global)
                    $existingEmployee = Employee::where('nik', $payload['nik'])->first();
                    $employee = null;
                    $previousEmail = null;

                    if ($existingEmployee) {
                        if ($existingEmployee->institution_id !== $institutionId) {
                            $errors[] = 'Baris '.$rowNumber.': NIK sudah terdaftar di institusi lain';
                            $errorCount++;

                            continue;
                        }

                        // Update jika sudah ada di institusi yang sama
                        $previousEmail = $existingEmployee->email;
                        $existingEmployee->update(array_merge($payload, [
                            'institution_id' => $institutionId,
                        ]));
                        $employee = $existingEmployee;
                        $successCount++;
                    } else {
                        // Create jika belum ada
                        $employee = Employee::create(array_merge($payload, [
                            'institution_id' => $institutionId,
                        ]));
                        $successCount++;
                    }

                    if ($employee) {
                        $importRole = $employee->type === 'Guru' ? 'teacher' : 'staff';
                        $accountResult = $this->ensureEmployeeUserAccount($employee, $previousEmail, null, $importRole);

                        if (! empty($accountResult['generated_password'])) {
                            $createdAccounts[] = [
                                'row' => $rowNumber,
                                'email' => $employee->email,
                                'password' => $accountResult['generated_password'],
                            ];
                        }

                        if (! empty($accountResult['user_conflict'])) {
                            $accountConflicts[] = [
                                'row' => $rowNumber,
                                'email' => $accountResult['user_conflict']['email'],
                                'role' => $accountResult['user_conflict']['role'],
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Baris '.$rowNumber.': '.$e->getMessage();
                    $errorCount++;
                    Log::error('Failed to import employee', [
                        'row' => $rowNumber,
                        'error' => $e->getMessage(),
                        'data' => $employeeData,
                    ]);
                }
            }

            Log::info('Employees imported', [
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'institution_id' => $institutionId,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Import selesai',
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'errors' => $errors,
                'created_accounts' => $createdAccounts,
                'account_conflicts' => $accountConflicts,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to import employees', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengimpor data pegawai',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Ensure employee user account exists and is in sync.
     * All employees with email can have an account (role: teacher or staff).
     */
    protected function ensureEmployeeUserAccount(Employee $employee, ?string $previousEmail = null, ?array $permissionKeys = null, ?string $userRole = null): array
    {
        $result = [
            'user_created' => false,
            'user_updated' => false,
            'generated_password' => null,
            'user_conflict' => null,
        ];

        if (empty($employee->email)) {
            return $result;
        }

        $role = in_array($userRole, ['teacher', 'staff'], true) ? $userRole : ($employee->type === 'Guru' ? 'teacher' : 'staff');
        $currentEmail = $employee->email;
        $existingUser = User::where('email', $currentEmail)->first();

        if ($existingUser) {
            if (in_array($existingUser->role, ['teacher', 'staff'], true)) {
                $existingUser->update([
                    'name' => $employee->name,
                    'institution_id' => $employee->institution_id,
                    'role' => $role,
                ]);
                if ($permissionKeys !== null) {
                    $this->syncPermissionsForUser(
                        $existingUser,
                        $this->resolvePermissionKeysForRole($employee, $role, $permissionKeys)
                    );
                }
                $result['user_updated'] = true;
            } else {
                $result['user_conflict'] = [
                    'email' => $currentEmail,
                    'role' => $existingUser->role,
                ];
            }

            return $result;
        }

        if ($previousEmail && $previousEmail !== $currentEmail) {
            $previousUser = User::where('email', $previousEmail)->first();
            if ($previousUser && in_array($previousUser->role, ['teacher', 'staff'], true)) {
                $previousUser->update([
                    'institution_id' => $employee->institution_id,
                    'name' => $employee->name,
                    'email' => $currentEmail,
                    'role' => $role,
                ]);
                if ($permissionKeys !== null) {
                    $this->syncPermissionsForUser(
                        $previousUser,
                        $this->resolvePermissionKeysForRole($employee, $role, $permissionKeys)
                    );
                }
                $result['user_updated'] = true;

                return $result;
            }
            if ($previousUser) {
                $result['user_conflict'] = [
                    'email' => $previousEmail,
                    'role' => $previousUser->role,
                ];

                return $result;
            }
        }

        $plainPassword = Str::random(10);

        $user = User::create([
            'institution_id' => $employee->institution_id,
            'name' => $employee->name,
            'email' => $currentEmail,
            'password' => Hash::make($plainPassword),
            'role' => $role,
            'email_verified_at' => now(),
        ]);

        $keysToAssign = $this->resolvePermissionKeysForRole(
            $employee,
            $role,
            $permissionKeys ?? TeacherAccess::defaultPermissionKeys()
        );
        $this->syncPermissionsForUser($user, $keysToAssign);

        Log::info('Employee user account created', [
            'employee_id' => $employee->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);

        $result['user_created'] = true;
        $result['generated_password'] = $plainPassword;

        return $result;
    }

    /**
     * Default module access for new teacher accounts.
     * Jurnal Mengajar, Nilai, dan Jadwal agar guru baru langsung bisa bekerja.
     * Persuratan hanya lewat tugas tambahan administratif / admin.
     */
    protected function getDefaultTeacherPermissions(): array
    {
        return TeacherAccess::defaultPermissionKeys();
    }

    /**
     * Guru / role teacher selalu mendapat paket mengajar (nilai, jurnal, jadwal).
     *
     * @param  list<string>  $permissionKeys
     * @return list<string>
     */
    protected function resolvePermissionKeysForRole(Employee $employee, string $role, array $permissionKeys): array
    {
        if ($role === 'teacher' || $employee->type === 'Guru') {
            return TeacherAccess::mergeTeachingDefaults($permissionKeys);
        }

        return array_values(array_unique(array_filter($permissionKeys)));
    }

    /**
     * Sync module permissions to a user.
     */
    protected function syncPermissionsForUser(User $user, array $permissionKeys): void
    {
        $keys = collect($permissionKeys)
            ->filter()
            ->unique()
            ->values();

        if ($keys->isEmpty()) {
            $user->permissions()->sync([]);

            return;
        }

        $permissionIds = Permission::whereIn('key', $keys)->pluck('id')->all();
        $user->permissions()->sync($permissionIds);
    }

    /**
     * Effective permission keys for employee: manual permission_keys merged with
     * permissions granted by all assigned additional duties (tugas tambahan).
     * Untuk guru, paket mengajar selalu digabung.
     */
    protected function getEffectivePermissionKeys(Employee $employee, ?array $manualKeys): array
    {
        $manual = $manualKeys ?? [];
        if ($employee->type === 'Guru') {
            $manual = TeacherAccess::mergeTeachingDefaults($manual);
        }
        $employee->unsetRelation('additionalDuties');
        $employee->load('activeAdditionalDuties.permissions');
        $fromDuties = $employee->activeAdditionalDuties->flatMap(fn ($d) => $d->permissions->pluck('key'))->unique()->values()->all();

        return ReportAccess::sanitizeKeysForEmployee(
            $employee,
            array_values(array_unique(array_merge($manual, $fromDuties)))
        );
    }

    /**
     * Sync jurusan Kaprog. Hanya berlaku jika duty kepala_program_keahlian aktif.
     *
     * @param  array<int, int|string>  $additionalDutyIds
     * @param  array<int, int|string>  $programKeahlianIds
     */
    protected function syncKaprogPrograms(Employee $employee, array $additionalDutyIds, array $programKeahlianIds): void
    {
        $kaprogDutyId = \App\Models\AdditionalDuty::query()
            ->where('key', \App\Support\KaprogAccess::DUTY_KEY)
            ->value('id');

        $hasKaprog = $kaprogDutyId && in_array((int) $kaprogDutyId, array_map('intval', $additionalDutyIds), true);
        if (! $hasKaprog) {
            $employee->programKeahlians()->sync([]);

            return;
        }

        $ids = array_values(array_unique(array_map('intval', $programKeahlianIds)));
        if ($ids === []) {
            $employee->programKeahlians()->sync([]);

            return;
        }

        $validIds = \App\Models\ProgramKeahlian::query()
            ->where('institution_id', $employee->institution_id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $employee->programKeahlians()->sync($validIds);
    }

    /**
     * Kolom ringkas untuk daftar pegawai (index).
     * photo_path hanya disertakan jika migrasi sudah dijalankan.
     *
     * @return array<int, string>
     */
    private function employeeIndexColumns(): array
    {
        $columns = [
            'id',
            'institution_id',
            'nik',
            'type',
            'nip',
            'nuptk',
            'name',
            'gender',
            'email',
            'subject',
            'status',
            'employment_status',
            'notes',
            'created_at',
        ];

        if (Schema::hasColumn('employee', 'photo_path')) {
            $columns[] = 'photo_path';
        }

        return $columns;
    }
}
