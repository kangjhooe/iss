<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentActionLogRequest;
use App\Http\Resources\StudentActionLogResource;
use App\Models\StudentActionLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StudentActionLogController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $query = StudentActionLog::with(['student:id,name,nis,nisn', 'recorder:id,name'])
                ->forInstitution($institutionId)
                ->orderBy('action_date', 'desc');

            if ($request->filled('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            $perPage = min($request->get('per_page', 15), 100);
            $items = $query->paginate($perPage);
            return StudentActionLogResource::collection($items);
        } catch (\Exception $e) {
            Log::error('StudentActionLog index failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil catatan tindakan.'], 500);
        }
    }

    public function store(StoreStudentActionLogRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $institutionId = $user->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $student = \App\Models\Student::where('id', $request->student_id)->where('institution_id', $institutionId)->firstOrFail();

            $log = StudentActionLog::create([
                'institution_id' => $institutionId,
                'student_id' => $student->id,
                'point_threshold_id' => $request->point_threshold_id,
                'action_name' => $request->action_name,
                'action_date' => $request->action_date,
                'recorded_by' => $user->id,
                'notes' => $request->notes,
            ]);
            $log->load(['student', 'recorder']);
            return (new StudentActionLogResource($log))->response()->setStatusCode(201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            Log::error('StudentActionLog store failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mencatat tindakan.'], 500);
        }
    }

    public function byStudent(Request $request, int $studentId): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $request->user()->institution_id;
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }
            $items = StudentActionLog::with('recorder')
                ->forInstitution($institutionId)
                ->forStudent($studentId)
                ->orderBy('action_date', 'desc')
                ->paginate(20);
            return StudentActionLogResource::collection($items);
        } catch (\Exception $e) {
            Log::error('StudentActionLog byStudent failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil riwayat tindakan.'], 500);
        }
    }
}
