<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LabBooking;
use App\Models\LessonSchedule;
use App\Models\Room;
use App\Notifications\LabNotification;
use App\Support\InstitutionContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LabBookingController extends Controller
{
    private function resolveInstitutionId(Request $request): ?int
    {
        return InstitutionContext::resolveForUser(
            $request->user(),
            $request,
            $request->get('institution_id')
        );
    }

    /**
     * Daftar lab untuk booking (bisa diakses guru/staff tanpa modul facility).
     */
    public function labsForBooking(Request $request)
    {
        try {
            $institutionId = $this->resolveInstitutionId($request);
            if (!$institutionId) {
                return response()->json(['data' => []]);
            }

            $labs = Room::with(['building:id,name', 'responsibleEmployee:id,name'])
                ->where('institution_id', $institutionId)
                ->where('type', 'Laboratorium')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'lab_type', 'building_id', 'condition', 'capacity', 'responsible_employee_id']);

            return response()->json(['data' => $labs]);
        } catch (\Exception $e) {
            Log::error('Failed to list labs for booking', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil daftar lab'], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $institutionId = $this->resolveInstitutionId($request);

            $query = LabBooking::with([
                'room:id,name,code,responsible_employee_id',
                'requesterEmployee:id,name,nip',
                'approver:id,name',
            ]);

            if ($institutionId) {
                $query->where('institution_id', $institutionId);
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }
            if ($request->boolean('mine')) {
                $employee = InstitutionContext::employeeForInstitution($user, $institutionId, $request);
                if ($employee) {
                    $query->where(function ($q) use ($employee, $user) {
                        $q->where('requester_employee_id', $employee->id)
                            ->orWhere('created_by', $user->id);
                    });
                } else {
                    $query->where('created_by', $user->id);
                }
            }

            $perPage = min((int) $request->get('per_page', 50), 100);
            $bookings = $query->orderByDesc('date')->orderBy('start_time')->paginate($perPage);

            return response()->json($bookings);
        } catch (\Exception $e) {
            Log::error('Failed to list lab bookings', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengambil data booking'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|exists:room,id',
            'purpose' => 'required|string|max:500',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
            'requester_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $user = $request->user();
            $room = Room::findOrFail($request->room_id);

            $room->load('responsibleEmployee');

            if ($room->type !== 'Laboratorium') {
                return response()->json(['message' => 'Ruangan bukan laboratorium'], 422);
            }
            if (!$user->canViewLab($room)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $conflict = $this->findConflict($room->id, $request->date, $request->start_time, $request->end_time);
            if ($conflict) {
                return response()->json(['message' => $conflict], 422);
            }

            $employee = InstitutionContext::employeeForInstitution($user, (int) $room->institution_id, $request);
            $isManager = $user->canManageLab($room);

            $booking = LabBooking::create([
                'institution_id' => $room->institution_id,
                'room_id' => $room->id,
                'requester_employee_id' => $employee?->id,
                'requester_name' => $request->requester_name ?: ($employee?->name ?: $user->name),
                'purpose' => $request->purpose,
                'date' => $request->date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => $isManager ? 'approved' : 'pending',
                'approved_by' => $isManager ? $user->id : null,
                'approved_at' => $isManager ? now() : null,
                'notes' => $request->notes,
                'created_by' => $user->id,
            ]);

            if (!$isManager && $room->responsibleEmployee) {
                $pjUser = $room->responsibleEmployee->userAccount;
                if ($pjUser) {
                    $pjUser->notify(new LabNotification(
                        'booking_pending',
                        "Ada ajuan booking lab {$room->name} dari " . ($booking->requester_name ?: 'pengguna') . '.',
                        ['room_id' => $room->id, 'booking_id' => $booking->id]
                    ));
                }
            }

            $booking->load(['room:id,name,code', 'requesterEmployee:id,name,nip']);

            return response()->json([
                'message' => $isManager ? 'Booking lab berhasil dibuat' : 'Ajuan booking lab berhasil dikirim',
                'data' => $booking,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create lab booking', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membuat booking'], 500);
        }
    }

    public function approve(Request $request, $id)
    {
        return $this->decide($request, $id, 'approved');
    }

    public function reject(Request $request, $id)
    {
        return $this->decide($request, $id, 'rejected');
    }

    public function cancel(Request $request, $id)
    {
        try {
            $booking = LabBooking::with('room')->findOrFail($id);
            $user = $request->user();
            $employee = InstitutionContext::employeeForInstitution(
                $user,
                (int) $booking->institution_id,
                $request
            );
            $isOwner = $employee && (int) $booking->requester_employee_id === (int) $employee->id;
            $isManager = $user->canManageLab($booking->room);

            if (!$isOwner && !$isManager) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            if (!in_array($booking->status, ['pending', 'approved'], true)) {
                return response()->json(['message' => 'Booking tidak dapat dibatalkan'], 422);
            }

            $booking->update([
                'status' => 'cancelled',
                'updated_by' => $user->id,
            ]);

            return response()->json(['message' => 'Booking dibatalkan', 'data' => $booking->fresh()]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Booking tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to cancel lab booking', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal membatalkan booking'], 500);
        }
    }

    protected function decide(Request $request, $id, string $status)
    {
        try {
            $booking = LabBooking::with('room')->findOrFail($id);
            $user = $request->user();

            if (!$user->canManageLab($booking->room)) {
                return response()->json(['message' => 'Hanya penanggung jawab atau admin yang dapat memproses booking'], 403);
            }
            if ($booking->status !== 'pending') {
                return response()->json(['message' => 'Booking sudah diproses'], 422);
            }

            if ($status === 'approved') {
                $conflict = $this->findConflict(
                    $booking->room_id,
                    $booking->date->format('Y-m-d'),
                    substr((string) $booking->start_time, 0, 5),
                    substr((string) $booking->end_time, 0, 5),
                    $booking->id
                );
                if ($conflict) {
                    return response()->json(['message' => $conflict], 422);
                }
            }

            $data = [
                'status' => $status,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'updated_by' => $user->id,
            ];
            if ($status === 'rejected') {
                $data['rejection_reason'] = $request->input('rejection_reason', $request->input('notes'));
            }

            $booking->update($data);
            $booking->load(['room', 'requesterEmployee']);

            // Notify requester about decision
            $requesterUser = $booking->requesterEmployee?->userAccount;
            if ($requesterUser) {
                $msg = $status === 'approved'
                    ? "Booking lab {$booking->room->name} Anda telah disetujui."
                    : "Booking lab {$booking->room->name} Anda ditolak.";
                $requesterUser->notify(new LabNotification(
                    $status === 'approved' ? 'booking_approved' : 'booking_rejected',
                    $msg,
                    ['room_id' => $booking->room_id, 'booking_id' => $booking->id]
                ));
            }

            return response()->json([
                'message' => $status === 'approved' ? 'Booking disetujui' : 'Booking ditolak',
                'data' => $booking->fresh(['room', 'requesterEmployee', 'approver']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Booking tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to decide lab booking', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal memproses booking'], 500);
        }
    }

    protected function findConflict(int $roomId, string $date, string $start, string $end, ?int $excludeId = null): ?string
    {
        $startTime = Carbon::parse($date . ' ' . $start);
        $endTime = Carbon::parse($date . ' ' . $end);

        $bookingQuery = LabBooking::where('room_id', $roomId)
            ->whereDate('date', $date)
            ->where('status', 'approved')
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                    ->where('end_time', '>', $start);
            });
        if ($excludeId) {
            $bookingQuery->where('id', '!=', $excludeId);
        }
        if ($bookingQuery->exists()) {
            return 'Bentrok dengan booking lab yang sudah disetujui';
        }

        $dayOfWeek = Carbon::parse($date)->dayOfWeekIso; // 1-7
        if ($dayOfWeek <= 5) {
            $schedules = LessonSchedule::where('room_id', $roomId)
                ->where('day_of_week', $dayOfWeek)
                ->get();
            foreach ($schedules as $s) {
                if (!$s->start_time || !$s->end_time) {
                    continue;
                }
                $sStart = substr((string) $s->start_time, 0, 5);
                $sEnd = substr((string) $s->end_time, 0, 5);
                if ($start < $sEnd && $end > $sStart) {
                    return 'Bentrok dengan jadwal pelajaran lab (' . ($s->day_name ?? 'hari ' . $dayOfWeek) . ' ' . $sStart . '-' . $sEnd . ')';
                }
            }
        }

        return null;
    }
}
