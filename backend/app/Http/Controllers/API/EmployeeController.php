<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Models\EmployeeEducation;
use App\Models\EmployeeDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        try {
            $query = Employee::query();

            // Filter berdasarkan institusi user yang login
            if (!$request->user()->isAdmin()) {
                $query->where('institution_id', $request->user()->institution_id);
            } elseif ($request->has('institution_id')) {
                $query->where('institution_id', $request->institution_id);
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
            $employees = $query->select(['id', 'institution_id', 'type', 'nip', 'nuptk', 'name', 'gender', 'status', 'employment_status', 'created_at'])
                ->with('institution:id,name')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

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
     * Store a newly created employee.
     */
    public function store(StoreEmployeeRequest $request)
    {
        try {
            $institutionId = $request->user()->isAdmin() 
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

            Log::info('Employee created', [
                'employee_id' => $employee->id,
                'institution_id' => $institutionId,
                'type' => $employee->type,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Pegawai berhasil ditambahkan',
                'data' => new EmployeeResource($employee->load(['institution', 'educations', 'documents'])),
            ], 201);
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
            $employee = Employee::with(['institution', 'educations', 'documents'])->findOrFail($id);

            // Jika bukan admin, hanya bisa melihat pegawai dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
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

            // Jika bukan admin, hanya bisa update pegawai dari institusi sendiri
            if (!$request->user()->isAdmin() && $request->user()->institution_id != $employee->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validated();
            
            // Extract educations if provided
            $educations = $validated['educations'] ?? null;
            unset($validated['educations']);

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

            Log::info('Employee updated', [
                'employee_id' => $employee->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Pegawai berhasil diperbarui',
                'data' => new EmployeeResource($employee->load(['institution', 'educations', 'documents'])),
            ]);
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

            foreach ($employeesData as $index => $employeeData) {
                try {
                    // Validasi data minimal
                    if (empty($employeeData['name'])) {
                        $errors[] = "Baris " . ($index + 1) . ": Nama Lengkap wajib diisi";
                        $errorCount++;
                        continue;
                    }

                    // Set default type jika tidak ada
                    if (empty($employeeData['type'])) {
                        $employeeData['type'] = 'Guru';
                    }

                    // Cek apakah pegawai sudah ada berdasarkan NIP atau NUPTK
                    $existingEmployee = null;
                    if (!empty($employeeData['nip'])) {
                        $existingEmployee = Employee::where('institution_id', $institutionId)
                            ->where('nip', $employeeData['nip'])
                            ->first();
                    } elseif (!empty($employeeData['nuptk'])) {
                        $existingEmployee = Employee::where('institution_id', $institutionId)
                            ->where('nuptk', $employeeData['nuptk'])
                            ->first();
                    }

                    if ($existingEmployee) {
                        // Update jika sudah ada
                        $existingEmployee->update(array_merge($employeeData, [
                            'institution_id' => $institutionId
                        ]));
                        $successCount++;
                    } else {
                        // Create jika belum ada
                        Employee::create(array_merge($employeeData, [
                            'institution_id' => $institutionId
                        ]));
                        $successCount++;
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
}
