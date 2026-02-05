<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveTeacherChangeRequestRequest;
use App\Http\Requests\StoreTeacherChangeRequestRequest;
use App\Models\Employee;
use App\Models\TeacherChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeacherChangeRequestController extends Controller
{
    public function allowedFields(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['teacherProfile', 'employeeProfile']);
        $isTeacher = $user->teacherProfile || $user->employeeProfile;
        if (!$isTeacher && !$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json([
            'data' => TeacherChangeRequest::ALLOWED_FIELDS,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = TeacherChangeRequest::with([
                'employee:id,name,email,nip,nuptk,institution_id',
                'employee.institution:id,name',
                'requester:id,name,email',
                'approver:id,name,email',
            ])->orderBy('created_at', 'desc');

            $user->load(['teacherProfile', 'employeeProfile']);
            $profile = $user->teacherProfile ?? $user->employeeProfile;

            if ($profile) {
                $query->where('requested_by', $user->id);
            } elseif ($user->isSuperAdmin()) {
                // super admin sees all
            } elseif ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
                $institutionId = $user->institution_id;
                if (!$institutionId) {
                    return response()->json(['data' => []], 200);
                }
                $query->whereHas('employee', fn ($q) => $q->where('institution_id', $institutionId));
            } else {
                return response()->json(['data' => []], 200);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return response()->json($items);
        } catch (\Exception $e) {
            Log::error('TeacherChangeRequest index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data permintaan perubahan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreTeacherChangeRequestRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['teacherProfile', 'employeeProfile']);
            $profile = $user->teacherProfile ?? $user->employeeProfile;

            if (!$profile) {
                return response()->json(['message' => 'Profil guru tidak ditemukan.'], 403);
            }

            $employee = Employee::find($profile->id);
            if (!$employee) {
                return response()->json(['message' => 'Data pegawai tidak ditemukan.'], 404);
            }

            $fieldName = $request->field_name;
            $newValue = $request->new_value;

            $existing = TeacherChangeRequest::where('employee_id', $employee->id)
                ->where('field_name', $fieldName)
                ->pending()
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Sudah ada permintaan pending untuk field ini.'], 422);
            }

            $oldValue = $employee->{$fieldName};
            if ($oldValue !== null && $employee->getRawOriginal($fieldName) !== null) {
                $oldValue = is_object($oldValue) ? $oldValue->format('Y-m-d') : (string) $oldValue;
            } else {
                $oldValue = $oldValue === null ? '' : (string) $oldValue;
            }
            if ((string) $newValue === (string) $oldValue) {
                return response()->json(['message' => 'Nilai baru sama dengan nilai saat ini.'], 422);
            }

            DB::beginTransaction();
            $changeRequest = TeacherChangeRequest::create([
                'employee_id' => $employee->id,
                'requested_by' => $user->id,
                'field_name' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => (string) $newValue,
                'status' => 'pending',
            ]);
            DB::commit();

            Log::info('Teacher change request created', [
                'request_id' => $changeRequest->id,
                'employee_id' => $employee->id,
                'field_name' => $fieldName,
            ]);

            return response()->json([
                'message' => 'Permintaan perubahan berhasil diajukan. Menunggu persetujuan admin.',
                'data' => $changeRequest->load(['employee:id,name,email', 'requester']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherChangeRequest store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengajukan permintaan perubahan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $changeRequest = TeacherChangeRequest::with(['employee', 'requester', 'approver'])->findOrFail($id);
            $user = $request->user();
            $user->load(['teacherProfile', 'employeeProfile']);
            $profile = $user->teacherProfile ?? $user->employeeProfile;

            if ($profile) {
                if ((int) $changeRequest->requested_by !== (int) $user->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            } elseif (!$user->isSuperAdmin()) {
                if ($user->isInstitutionAdmin() || $user->isAdminOrSuperAdmin()) {
                    if ($changeRequest->employee->institution_id !== $user->institution_id) {
                        return response()->json(['message' => 'Unauthorized'], 403);
                    }
                } else {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            return response()->json($changeRequest);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('TeacherChangeRequest show failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data.'], 500);
        }
    }

    public function approve(ApproveTeacherChangeRequestRequest $request, int $id): JsonResponse
    {
        try {
            $changeRequest = TeacherChangeRequest::with('employee')->findOrFail($id);

            if ($changeRequest->status !== 'pending') {
                return response()->json(['message' => 'Permintaan sudah diproses.'], 422);
            }

            $user = $request->user();
            if (!$user->isSuperAdmin() && $changeRequest->employee->institution_id !== $user->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                $employee = $changeRequest->employee;
                $fieldName = $changeRequest->field_name;
                $newValue = $changeRequest->new_value;

                if (in_array($fieldName, ['birth_date', 'join_date', 'certification_date'], true)) {
                    $employee->{$fieldName} = $newValue ? \Carbon\Carbon::parse($newValue) : null;
                } else {
                    $employee->{$fieldName} = $newValue;
                }
                $employee->save();

                $changeRequest->status = 'approved';
                $changeRequest->approved_by = $user->id;
                $changeRequest->approved_at = now();
                $changeRequest->save();

                Log::info('Teacher change request approved', [
                    'request_id' => $changeRequest->id,
                    'employee_id' => $employee->id,
                    'approved_by' => $user->id,
                ]);
                $message = 'Permintaan berhasil disetujui. Data guru telah diperbarui.';
            } else {
                $changeRequest->status = 'rejected';
                $changeRequest->approved_by = $user->id;
                $changeRequest->rejection_reason = $request->rejection_reason;
                $changeRequest->approved_at = now();
                $changeRequest->save();

                Log::info('Teacher change request rejected', [
                    'request_id' => $changeRequest->id,
                    'rejected_by' => $user->id,
                ]);
                $message = 'Permintaan berhasil ditolak.';
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'data' => $changeRequest->load(['employee:id,name,email,institution_id', 'requester', 'approver']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('TeacherChangeRequest approve failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal memproses permintaan.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function pendingCount(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load(['teacherProfile', 'employeeProfile']);
            $profile = $user->teacherProfile ?? $user->employeeProfile;
            if ($profile) {
                return response()->json(['count' => TeacherChangeRequest::where('requested_by', $user->id)->pending()->count()], 200);
            }

            if (!$user->isAdminOrSuperAdmin() && !$user->isInstitutionAdmin()) {
                return response()->json(['count' => 0], 200);
            }

            $query = TeacherChangeRequest::pending();
            if (!$user->isSuperAdmin()) {
                $institutionId = $user->institution_id;
                if (!$institutionId) {
                    return response()->json(['count' => 0], 200);
                }
                $query->whereHas('employee', fn ($q) => $q->where('institution_id', $institutionId));
            }

            return response()->json(['count' => $query->count()]);
        } catch (\Exception $e) {
            Log::error('TeacherChangeRequest pendingCount failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil jumlah.', 'count' => 0], 500);
        }
    }
}
