<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeInstitutionAssignmentResource;
use App\Models\Employee;
use App\Models\EmployeeInstitutionAssignment;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeInstitutionAssignmentController extends Controller
{
    /**
     * List pending non-induk assignment requests for primary institution.
     */
    public function pending(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user->isInstitutionAdmin() && !$user->isAdminOrSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $query = EmployeeInstitutionAssignment::query()
                ->pending()
                ->with([
                    'employee:id,name,nik,institution_id',
                    'employee.institution:id,name',
                    'institution:id,name',
                    'requester',
                    'approver',
                ])
                ->orderBy('created_at', 'desc');

            if ($user->isAdminOrSuperAdmin()) {
                if ($request->has('institution_id')) {
                    $institutionId = $request->get('institution_id');
                    $query->whereHas('employee', function ($employeeQuery) use ($institutionId) {
                        $employeeQuery->where('institution_id', $institutionId);
                    });
                }
            } else {
                $institutionId = InstitutionContext::resolveForUser($user, $request, $request->get('institution_id'));
                if (!$institutionId) {
                    return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
                }
                $query->whereHas('employee', function ($employeeQuery) use ($institutionId) {
                    $employeeQuery->where('institution_id', $institutionId);
                });
            }

            $perPage = min($request->get('per_page', 15), 100);
            $assignments = $query->paginate($perPage);

            return EmployeeInstitutionAssignmentResource::collection($assignments);
        } catch (\Exception $e) {
            Log::error('Failed to list pending employee assignments', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data permintaan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Create a new non-induk assignment request.
     */
    public function store(Request $request, Employee $employee)
    {
        try {
            $user = $request->user();

            if (!$user->isInstitutionAdmin() && !$user->isAdminOrSuperAdmin()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validated = $request->validate([
                'institution_id' => 'sometimes|exists:institution,id',
                'subject' => 'nullable|string|max:255',
                'assignment_title' => 'nullable|string|max:255',
                'assignment_notes' => 'nullable|string',
            ]);

            $institutionId = InstitutionContext::resolveForUser(
                $user,
                $request,
                $validated['institution_id'] ?? null
            );

            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan'], 400);
            }

            if ($employee->institution_id == $institutionId) {
                return response()->json([
                    'message' => 'Pegawai sudah terdaftar sebagai induk di institusi ini',
                ], 422);
            }

            $existingAssignment = EmployeeInstitutionAssignment::where('employee_id', $employee->id)
                ->where('institution_id', $institutionId)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existingAssignment) {
                return response()->json([
                    'message' => 'Permintaan non-induk sudah ada atau sudah disetujui',
                ], 422);
            }

            $assignment = EmployeeInstitutionAssignment::create([
                'employee_id' => $employee->id,
                'institution_id' => $institutionId,
                'assignment_type' => 'non_induk',
                'status' => 'pending',
                'subject' => $validated['subject'] ?? null,
                'assignment_title' => $validated['assignment_title'] ?? null,
                'assignment_notes' => $validated['assignment_notes'] ?? null,
                'requested_by' => $user->id,
            ]);

            return response()->json([
                'message' => 'Permintaan non-induk berhasil dibuat',
                'data' => new EmployeeInstitutionAssignmentResource($assignment->load(['institution', 'requester', 'employee.institution'])),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create employee assignment request', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat membuat permintaan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Approve a non-induk assignment request.
     */
    public function approve(Request $request, EmployeeInstitutionAssignment $assignment)
    {
        try {
            $user = $request->user();

            if (!$user->isAdminOrSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $assignment->employee->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($assignment->status !== 'pending') {
                return response()->json(['message' => 'Permintaan sudah diproses'], 422);
            }

            $validated = $request->validate([
                'subject' => 'nullable|string|max:255',
                'assignment_title' => 'nullable|string|max:255',
                'assignment_notes' => 'nullable|string',
                'started_at' => 'nullable|date',
            ]);

            $assignment->update([
                'status' => 'approved',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'started_at' => $validated['started_at'] ?? now()->toDateString(),
                'subject' => $validated['subject'] ?? $assignment->subject,
                'assignment_title' => $validated['assignment_title'] ?? $assignment->assignment_title,
                'assignment_notes' => $validated['assignment_notes'] ?? $assignment->assignment_notes,
            ]);

            return response()->json([
                'message' => 'Permintaan non-induk disetujui',
                'data' => new EmployeeInstitutionAssignmentResource($assignment->load(['institution', 'requester', 'approver', 'employee.institution'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to approve employee assignment', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menyetujui permintaan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reject a non-induk assignment request.
     */
    public function reject(Request $request, EmployeeInstitutionAssignment $assignment)
    {
        try {
            $user = $request->user();

            if (!$user->isAdminOrSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $assignment->employee->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($assignment->status !== 'pending') {
                return response()->json(['message' => 'Permintaan sudah diproses'], 422);
            }

            $validated = $request->validate([
                'rejection_reason' => 'required|string',
            ]);

            $assignment->update([
                'status' => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);

            return response()->json([
                'message' => 'Permintaan non-induk ditolak',
                'data' => new EmployeeInstitutionAssignmentResource($assignment->load(['institution', 'requester', 'approver', 'employee.institution'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to reject employee assignment', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat menolak permintaan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Update assignment data (non-induk).
     */
    public function update(Request $request, EmployeeInstitutionAssignment $assignment)
    {
        try {
            $user = $request->user();

            if (!$user->isAdminOrSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $assignment->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if (!in_array($assignment->status, ['pending', 'approved'], true)) {
                return response()->json(['message' => 'Penugasan tidak dapat diperbarui'], 422);
            }

            $validated = $request->validate([
                'subject' => 'nullable|string|max:255',
                'assignment_title' => 'nullable|string|max:255',
                'assignment_notes' => 'nullable|string',
            ]);

            $assignment->update($validated);

            return response()->json([
                'message' => 'Penugasan non-induk diperbarui',
                'data' => new EmployeeInstitutionAssignmentResource($assignment->load(['institution', 'requester', 'approver', 'employee.institution'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to update employee assignment', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat memperbarui penugasan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * End a non-induk assignment (history).
     */
    public function end(Request $request, EmployeeInstitutionAssignment $assignment)
    {
        try {
            $user = $request->user();

            if (!$user->isAdminOrSuperAdmin() && !InstitutionContext::canAccessInstitution($user, (int) $assignment->employee->institution_id)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($assignment->status !== 'approved') {
                return response()->json(['message' => 'Penugasan belum aktif'], 422);
            }

            $validated = $request->validate([
                'ended_reason' => 'nullable|string',
                'ended_at' => 'nullable|date',
            ]);

            $assignment->update([
                'status' => 'ended',
                'ended_at' => $validated['ended_at'] ?? now()->toDateString(),
                'ended_reason' => $validated['ended_reason'] ?? null,
            ]);

            return response()->json([
                'message' => 'Penugasan non-induk diakhiri',
                'data' => new EmployeeInstitutionAssignmentResource($assignment->load(['institution', 'requester', 'approver', 'employee.institution'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to end employee assignment', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan saat mengakhiri penugasan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
