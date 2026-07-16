<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LabUsageJournal;
use App\Models\Room;
use App\Support\InstitutionContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LabUsageJournalController extends Controller
{
    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    public function index(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);

            $query = LabUsageJournal::with([
                'room:id,name,code',
                'schoolClass:id,name',
                'subject:id,name',
                'recorder:id,name',
            ]);

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }

            $perPage = min((int) $request->get('per_page', 50), 100);
            $journals = $query->orderByDesc('date')->orderByDesc('id')->paginate($perPage);

            return response()->json($journals);
        } catch (\Exception $e) {
            Log::error('Failed to list lab journals', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil jurnal lab'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:room,id',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'activity' => 'required|string|max:500',
            'participants_count' => 'nullable|integer|min:0',
            'class_id' => 'nullable|exists:class,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'lab_booking_id' => 'nullable|exists:lab_booking,id',
            'lesson_schedule_id' => 'nullable|exists:lesson_schedules,id',
            'notes' => 'nullable|string',
            'incident_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            $room = Room::findOrFail($request->room_id);

            if ($room->type !== 'Laboratorium') {
                return response()->json(['message' => 'Ruangan bukan laboratorium'], 422);
            }
            if (!$user->canManageLab($room)) {
                return response()->json(['message' => 'Anda tidak berwenang mencatat jurnal lab ini'], 403);
            }

            $data = $validator->validated();
            $data['institution_id'] = $room->institution_id;
            $data['recorded_by'] = $user->id;
            $data['created_by'] = $user->id;

            $journal = LabUsageJournal::create($data);
            $journal->load(['room:id,name,code', 'schoolClass:id,name', 'subject:id,name', 'recorder:id,name']);

            return response()->json([
                'message' => 'Jurnal pemakaian lab berhasil dicatat',
                'data' => $journal,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create lab journal', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menyimpan jurnal'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'activity' => 'sometimes|string|max:500',
            'participants_count' => 'nullable|integer|min:0',
            'class_id' => 'nullable|exists:class,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'notes' => 'nullable|string',
            'incident_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $journal = LabUsageJournal::with('room')->findOrFail($id);
            $user = $request->user();

            if (!$user->canManageLab($journal->room)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $data = $validator->validated();
            $data['updated_by'] = $user->id;
            $journal->update($data);

            return response()->json([
                'message' => 'Jurnal berhasil diperbarui',
                'data' => $journal->fresh(['room:id,name,code', 'schoolClass:id,name', 'subject:id,name', 'recorder:id,name']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jurnal tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update lab journal', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memperbarui jurnal'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $journal = LabUsageJournal::with('room')->findOrFail($id);
            $user = $request->user();

            if (!$user->canManageLab($journal->room)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $journal->delete();
            return response()->json(['message' => 'Jurnal berhasil dihapus']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Jurnal tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete lab journal', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menghapus jurnal'], 500);
        }
    }
}
