<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLabScheduleRequest;
use App\Http\Requests\UpdateLabScheduleRequest;
use App\Http\Resources\LabScheduleResource;
use App\Models\LabSchedule;
use App\Services\LabScheduleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class LabScheduleController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected LabScheduleService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $semesterId = $request->get('semester_id');
            $roomId = $request->get('room_id');
            if (!$semesterId || !$roomId) {
                return response()->json(['message' => 'room_id dan semester_id wajib diisi.'], 422);
            }

            $rows = $this->service->listForRoom(
                $institutionId,
                (int) $roomId,
                (int) $semesterId
            );

            return LabScheduleResource::collection($rows);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            Log::error('LabSchedule index failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memuat jadwal lab.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function store(StoreLabScheduleRequest $request): JsonResponse
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
            }

            $schedule = $this->service->create($institutionId, $request->validated());

            return response()->json([
                'message' => 'Jadwal lab berhasil ditambahkan.',
                'data' => new LabScheduleResource($schedule),
            ], 201);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            Log::error('LabSchedule store failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menyimpan jadwal lab.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(UpdateLabScheduleRequest $request, LabSchedule $lab_schedule): JsonResponse
    {
        try {
            if ($denied = $this->denyUnlessOwns($request, $lab_schedule)) {
                return $denied;
            }

            $schedule = $this->service->update($lab_schedule, $request->validated());

            return response()->json([
                'message' => 'Jadwal lab berhasil diperbarui.',
                'data' => new LabScheduleResource($schedule),
            ]);
        } catch (ValidationException $e) {
            return $this->validationError($e);
        } catch (\Exception $e) {
            Log::error('LabSchedule update failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal memperbarui jadwal lab.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy(Request $request, LabSchedule $lab_schedule): JsonResponse
    {
        try {
            if ($denied = $this->denyUnlessOwns($request, $lab_schedule)) {
                return $denied;
            }

            $this->service->delete($lab_schedule);

            return response()->json(['message' => 'Jadwal lab berhasil dihapus.']);
        } catch (\Exception $e) {
            Log::error('LabSchedule destroy failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Gagal menghapus jadwal lab.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    private function denyUnlessOwns(Request $request, LabSchedule $schedule): ?JsonResponse
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        if ((int) $schedule->institution_id !== (int) $institutionId) {
            return response()->json(['message' => 'Jadwal lab tidak ditemukan.'], 404);
        }

        return null;
    }

    private function validationError(ValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => collect($e->errors())->flatten()->first() ?: 'Validasi gagal.',
            'errors' => $e->errors(),
        ], 422);
    }
}
