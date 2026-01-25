<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeDocument;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
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

            // Filter berdasarkan institusi user yang login
            if (!$user->isAdminOrSuperAdmin()) {
                $institutionId = $user->institution_id;
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
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('nip', 'like', '%' . $search . '%')
                      ->orWhere('nuptk', 'like', '%' . $search . '%');
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

            $perPage = min($request->get('per_page', 15), 100); // Max 100 per page
            $relations = ['institution:id,name'];
            if ($institutionId) {
                $relations['assignments'] = function ($assignmentQuery) use ($institutionId) {
                    $assignmentQuery->where('institution_id', $institutionId)
                        ->where('status', 'approved');
                };
            }

            $employees = $query->select(['id', 'institution_id', 'nik', 'type', 'nip', 'nuptk', 'name', 'gender', 'subject', 'status', 'employment_status', 'created_at'])
                ->with($relations)
                ->orderBy('created_at', 'desc')
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
     * Search employee by NIK (for non-induk requests).
     */
    public function searchByNik(Request $request)
    {
        $request->validate([
            'nik' => 'required|string|size:16|regex:/^[0-9]{16}$/',
        ]);

        $user = $request->user();
        if (!$user->isInstitutionAdmin() && !$user->isAdminOrSuperAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $employee = Employee::with('institution:id,name,npsn')->where('nik', $request->nik)->first();

        if (!$employee) {
            return response()->json(['message' => 'Pegawai tidak ditemukan'], 404);
        }

        return response()->json([
            'data' => [
                'id' => $employee->id,
                'nik' => $employee->nik,
                'name' => $employee->name,
                'gender' => $employee->gender,
                'type' => $employee->type,
                'institution' => $employee->institution ? [
                    'id' => $employee->institution->id,
                    'name' => $employee->institution->name,
                    'npsn' => $employee->institution->npsn,
                ] : null,
            ],
        ]);
    }

    /**
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $institutionId = $request->user()->isAdminOrSuperAdmin() 
                ? $request->institution_id 
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $validated = $request->validated();
            $validated['institution_id'] = $institutionId;

            // Extract educations if provided
            $educations = $validated['educations'] ?? [];
            unset($validated['educations']);

            $permissionKeys = $validated['permission_keys'] ?? null;
            unset($validated['permission_keys']);
            if (!$request->user()->isAdminOrSuperAdmin() && !$request->user()->isInstitutionAdmin()) {
                $permissionKeys = null;
            }

            $employee = Employee::create($validated);

            // Save educations
            if (!empty($educations)) {
                foreach ($educations as $index => $education) {
                    if (!empty($education['level'])) {
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

            $accountResult = $this->ensureTeacherUserAccount($employee, null, $permissionKeys);

            Log::info('Employee created', [
                'employee_id' => $employee->id,
                'institution_id' => $institutionId,
                'type' => $employee->type,
                'user_id' => $request->user()->id,
            ]);

            $response = [
                'message' => 'Pegawai berhasil ditambahkan',
                'data' => new EmployeeResource($employee->load(['institution', 'educations', 'documents', 'userAccount.permissions'])),
            ];

            if (!empty($accountResult['user_created'])) {
                $response['user_created'] = true;
                $response['generated_password'] = $accountResult['generated_password'];
            }

            if (!empty($accountResult['user_updated'])) {
                $response['user_updated'] = true;
            }

            if (!empty($accountResult['user_conflict'])) {
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
     * Display the specified employee.
     */
    public function show(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $previousEmail = $employee->email;
            $user = $request->user();
            $currentInstitutionId = $request->get('institution_id') ?? $user->institution_id;

            // Jika bukan admin/super admin, hanya bisa melihat pegawai dari institusi sendiri atau non-induk yang disetujui
            if (!$user->isAdminOrSuperAdmin() && $currentInstitutionId != $employee->institution_id) {
                $hasApprovedAssignment = $employee->assignments()
                    ->where('institution_id', $currentInstitutionId)
                    ->where('status', 'approved')
                    ->exists();

                if (!$hasApprovedAssignment) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            $relations = ['institution', 'educations', 'documents', 'userAccount.permissions'];

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
     * Update the specified employee.
     */
    public function update(UpdateEmployeeRequest $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            $previousEmail = $employee->email;

            // Jika bukan admin, hanya bisa update pegawai dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validated();
            
            // Extract educations if provided
            $educations = $validated['educations'] ?? null;
            unset($validated['educations']);

            $permissionKeys = null;
            if (array_key_exists('permission_keys', $validated)) {
                $permissionKeys = $validated['permission_keys'];
            }
            unset($validated['permission_keys']);
            if (!$request->user()->isAdminOrSuperAdmin() && !$request->user()->isInstitutionAdmin()) {
                $permissionKeys = null;
            }

            $employee->update($validated);

            // Update educations if provided
            if ($educations !== null) {
                // Delete existing educations
                $employee->educations()->delete();
                
                // Create new educations
                foreach ($educations as $index => $education) {
                    if (!empty($education['level'])) {
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

            $accountResult = $this->ensureTeacherUserAccount($employee, $previousEmail, $permissionKeys);

            Log::info('Employee updated', [
                'employee_id' => $employee->id,
                'user_id' => $request->user()->id,
            ]);

            $response = [
                'message' => 'Pegawai berhasil diperbarui',
                'data' => new EmployeeResource($employee->load(['institution', 'educations', 'documents', 'userAccount.permissions'])),
            ];

            if (!empty($accountResult['user_created'])) {
                $response['user_created'] = true;
                $response['generated_password'] = $accountResult['generated_password'];
            }

            if (!empty($accountResult['user_updated'])) {
                $response['user_updated'] = true;
            }

            if (!empty($accountResult['user_conflict'])) {
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
     * Remove the specified employee.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            // Jika bukan admin, hanya bisa hapus pegawai dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
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

            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
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
     * Upload document for employee.
     */
    public function uploadDocument(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);

            // Jika bukan admin, hanya bisa upload dokumen pegawai dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Check document count limit (max 20)
            $documentCount = $employee->documents()->count();
            if ($documentCount >= 20) {
                return response()->json([
                    'message' => 'Maksimal 20 file dokumen per pegawai'
                ], 400);
            }

            $request->validate([
                'file' => 'required|file|mimes:pdf|max:2048', // Max 2MB
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
            ]);

            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('employee_documents/' . $employee->id, $fileName, 'public');

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
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
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
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $document = $employee->documents()->findOrFail($documentId);

            if (!Storage::disk('public')->exists($document->file_path)) {
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

    /**
     * Import employees from Excel data.
     */
    public function import(Request $request)
    {
        try {
            $employeesData = $request->input('employees', []);
            
            if (empty($employeesData) || !is_array($employeesData)) {
                return response()->json([
                    'message' => 'Data pegawai tidak valid',
                ], 400);
            }

            $institutionId = $request->user()->isAdmin() 
                ? $request->input('institution_id')
                : $request->user()->institution_id;

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            $successCount = 0;
            $errorCount = 0;
            $errors = [];
            $createdAccounts = [];
            $accountConflicts = [];

            foreach ($employeesData as $index => $employeeData) {
                try {
                    // Validasi data minimal
                    if (empty($employeeData['name'])) {
                        $errors[] = "Baris " . ($index + 1) . ": Nama Lengkap wajib diisi";
                        $errorCount++;
                        continue;
                    }

                    if (empty($employeeData['nik'])) {
                        $errors[] = "Baris " . ($index + 1) . ": NIK wajib diisi";
                        $errorCount++;
                        continue;
                    }
                    if (!preg_match('/^[0-9]{16}$/', $employeeData['nik'])) {
                        $errors[] = "Baris " . ($index + 1) . ": Format NIK tidak valid";
                        $errorCount++;
                        continue;
                    }

                    // Set default type jika tidak ada
                    if (empty($employeeData['type'])) {
                        $employeeData['type'] = 'Guru';
                    }

                    if ($employeeData['type'] === 'Guru' && empty($employeeData['email'])) {
                        $errors[] = "Baris " . ($index + 1) . ": Email wajib diisi untuk guru";
                        $errorCount++;
                        continue;
                    }

                    // Cek apakah pegawai sudah ada berdasarkan NIK (global)
                    $existingEmployee = Employee::where('nik', $employeeData['nik'])->first();
                    $employee = null;
                    $previousEmail = null;

                    if ($existingEmployee) {
                        if ($existingEmployee->institution_id !== $institutionId) {
                            $errors[] = "Baris " . ($index + 1) . ": NIK sudah terdaftar di institusi lain";
                            $errorCount++;
                            continue;
                        }

                        // Update jika sudah ada di institusi yang sama
                        $previousEmail = $existingEmployee->email;
                        $existingEmployee->update(array_merge($employeeData, [
                            'institution_id' => $institutionId,
                        ]));
                        $employee = $existingEmployee;
                        $successCount++;
                    } else {
                        // Create jika belum ada
                        $employee = Employee::create(array_merge($employeeData, [
                            'institution_id' => $institutionId
                        ]));
                        $successCount++;
                    }

                    if ($employee) {
                        $accountResult = $this->ensureTeacherUserAccount($employee, $previousEmail, null);

                        if (!empty($accountResult['generated_password'])) {
                            $createdAccounts[] = [
                                'row' => $index + 1,
                                'email' => $employee->email,
                                'password' => $accountResult['generated_password'],
                            ];
                        }

                        if (!empty($accountResult['user_conflict'])) {
                            $accountConflicts[] = [
                                'row' => $index + 1,
                                'email' => $accountResult['user_conflict']['email'],
                                'role' => $accountResult['user_conflict']['role'],
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
                    $errorCount++;
                    Log::error('Failed to import employee', [
                        'row' => $index + 1,
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
     * Ensure teacher user account exists and is in sync.
     */
    protected function ensureTeacherUserAccount(Employee $employee, ?string $previousEmail = null, ?array $permissionKeys = null): array
    {
        $result = [
            'user_created' => false,
            'user_updated' => false,
            'generated_password' => null,
            'user_conflict' => null,
        ];

        if ($employee->type !== 'Guru' || empty($employee->email)) {
            return $result;
        }

        $currentEmail = $employee->email;
        $existingUser = User::where('email', $currentEmail)->first();

        if ($existingUser) {
            if ($existingUser->role === 'teacher') {
                $existingUser->update([
                    'name' => $employee->name,
                    'institution_id' => $employee->institution_id,
                ]);
                if ($permissionKeys !== null) {
                    $this->syncPermissionsForUser($existingUser, $permissionKeys);
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
            if ($previousUser) {
                if ($previousUser->role === 'teacher') {
                    $previousUser->update([
                        'institution_id' => $employee->institution_id,
                        'name' => $employee->name,
                        'email' => $currentEmail,
                    ]);
                    if ($permissionKeys !== null) {
                        $this->syncPermissionsForUser($previousUser, $permissionKeys);
                    }
                    $result['user_updated'] = true;
                } else {
                    $result['user_conflict'] = [
                        'email' => $previousEmail,
                        'role' => $previousUser->role,
                    ];
                }

                return $result;
            }
        }

        $plainPassword = Str::random(10);

        $user = User::create([
            'institution_id' => $employee->institution_id,
            'name' => $employee->name,
            'email' => $currentEmail,
            'password' => Hash::make($plainPassword),
            'role' => 'teacher',
            'email_verified_at' => now(),
        ]);

        $keysToAssign = $permissionKeys ?? $this->getDefaultTeacherPermissions();
        $this->syncPermissionsForUser($user, $keysToAssign);

        Log::info('Teacher user account created', [
            'employee_id' => $employee->id,
            'user_id' => $user->id,
        ]);

        $result['user_created'] = true;
        $result['generated_password'] = $plainPassword;

        return $result;
    }

    /**
     * Default module access for new teacher accounts.
     */
    protected function getDefaultTeacherPermissions(): array
    {
        return ['correspondence'];
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
}
