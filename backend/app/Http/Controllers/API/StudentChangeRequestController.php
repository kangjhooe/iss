<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveStudentChangeRequestRequest;
use App\Http\Requests\StoreStudentChangeRequestRequest;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentChangeRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $query = StudentChangeRequest::with(['student:id,name,nis,email,institution_id', 'requester:id,name,email', 'approver:id,name,email'])
                ->orderBy('created_at', 'desc');

            if ($user->isStudent()) {
                $profile = $user->studentProfile;
                if (!$profile) {
                    return response()->json(['data' => []], 200);
                }
                $query->where('student_id', $profile->id);
            } elseif (!$user->isSuperAdmin()) {
                $institutionId = $user->institution_id;
                if (!$institutionId) {
                    return response()->json(['data' => []], 200);
                }
                $query->whereHas('student', fn ($q) => $q->where('institution_id', $institutionId));
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);

            return response()->json($items);
        } catch (\Exception $e) {
            Log::error('StudentChangeRequest index failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengambil data permintaan perubahan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreStudentChangeRequestRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $profile = $user->studentProfile;
            if (!$profile || (int) $profile->id !== (int) $request->student_id) {
                return response()->json(['message' => 'Anda hanya dapat mengajukan perubahan untuk data diri sendiri.'], 403);
            }

            $student = Student::find($request->student_id);
            if (!$student) {
                return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
            }

            $fieldName = $request->field_name;
            $newValue = $request->new_value;

            $existing = StudentChangeRequest::where('student_id', $student->id)
                ->where('field_name', $fieldName)
                ->pending()
                ->first();

            if ($existing) {
                return response()->json(['message' => 'Sudah ada permintaan pending untuk field ini.'], 422);
            }

            $oldValue = $student->{$fieldName};
            if ($oldValue !== null && $student->getRawOriginal($fieldName) !== null) {
                $oldValue = is_object($oldValue) ? $oldValue->format('Y-m-d') : (string) $oldValue;
            } else {
                $oldValue = $oldValue === null ? '' : (string) $oldValue;
            }
            if ($newValue === $oldValue || (string) $newValue === (string) $oldValue) {
                return response()->json(['message' => 'Nilai baru sama dengan nilai saat ini.'], 422);
            }

            DB::beginTransaction();
            $changeRequest = StudentChangeRequest::create([
                'student_id' => $student->id,
                'requested_by' => $user->id,
                'field_name' => $fieldName,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'status' => 'pending',
            ]);
            DB::commit();

            Log::info('Student change request created', [
                'request_id' => $changeRequest->id,
                'student_id' => $student->id,
                'field_name' => $fieldName,
            ]);

            return response()->json([
                'message' => 'Permintaan perubahan berhasil diajukan. Menunggu persetujuan admin.',
                'data' => $changeRequest->load(['student:id,name,nis', 'requester']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StudentChangeRequest store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Gagal mengajukan permintaan perubahan',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $changeRequest = StudentChangeRequest::with(['student', 'requester', 'approver'])->findOrFail($id);
            $user = $request->user();

            if ($user->isStudent()) {
                $profile = $user->studentProfile;
                if (!$profile || $changeRequest->student_id !== (int) $profile->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            } elseif (!$user->isSuperAdmin()) {
                if ($changeRequest->student->institution_id !== $user->institution_id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            return response()->json($changeRequest);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('StudentChangeRequest show failed', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data.'], 500);
        }
    }

    public function approve(ApproveStudentChangeRequestRequest $request, int $id): JsonResponse
    {
        try {
            $changeRequest = StudentChangeRequest::with('student')->findOrFail($id);

            if ($changeRequest->status !== 'pending') {
                return response()->json(['message' => 'Permintaan sudah diproses.'], 422);
            }

            $user = $request->user();
            if (!$user->isSuperAdmin() && $changeRequest->student->institution_id !== $user->institution_id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            DB::beginTransaction();

            if ($request->action === 'approve') {
                $student = $changeRequest->student;
                $fieldName = $changeRequest->field_name;
                $newValue = $changeRequest->new_value;

                if (in_array($fieldName, ['father_birth_date', 'mother_birth_date', 'guardian_birth_date', 'birth_date'], true)) {
                    $student->{$fieldName} = $newValue ? \Carbon\Carbon::parse($newValue) : null;
                } else {
                    $student->{$fieldName} = $newValue;
                }
                $student->save();

                $changeRequest->status = 'approved';
                $changeRequest->approved_by = $user->id;
                $changeRequest->approved_at = now();
                $changeRequest->save();

                Log::info('Student change request approved', [
                    'request_id' => $changeRequest->id,
                    'student_id' => $student->id,
                    'approved_by' => $user->id,
                ]);
                $message = 'Permintaan berhasil disetujui. Data siswa telah diperbarui.';
            } else {
                $changeRequest->status = 'rejected';
                $changeRequest->approved_by = $user->id;
                $changeRequest->rejection_reason = $request->rejection_reason;
                $changeRequest->approved_at = now();
                $changeRequest->save();

                Log::info('Student change request rejected', [
                    'request_id' => $changeRequest->id,
                    'rejected_by' => $user->id,
                ]);
                $message = 'Permintaan berhasil ditolak.';
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'data' => $changeRequest->load(['student:id,name,nis,institution_id', 'requester', 'approver']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Permintaan tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StudentChangeRequest approve failed', ['id' => $id, 'error' => $e->getMessage()]);
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
            if ($user->isStudent()) {
                return response()->json(['count' => 0], 200);
            }

            $query = StudentChangeRequest::pending();
            if (!$user->isSuperAdmin()) {
                $institutionId = $user->institution_id;
                if (!$institutionId) {
                    return response()->json(['count' => 0], 200);
                }
                $query->whereHas('student', fn ($q) => $q->where('institution_id', $institutionId));
            }

            return response()->json(['count' => $query->count()]);
        } catch (\Exception $e) {
            Log::error('StudentChangeRequest pendingCount failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil jumlah.', 'count' => 0], 500);
        }
    }

    /**
     * Get list of allowed fields for student self-service (for frontend form).
     */
    public function allowedFields(Request $request): JsonResponse
    {
        if (!$request->user()?->isStudent()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json([
            'data' => StudentChangeRequest::ALLOWED_FIELDS,
        ]);
    }
}
