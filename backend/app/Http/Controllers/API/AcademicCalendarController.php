<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Controllers\API\Concerns\ResolvesInstitution;
use App\Http\Requests\StoreAcademicCalendarEventRequest;
use App\Http\Requests\UpdateAcademicCalendarEventRequest;
use App\Http\Resources\AcademicCalendarEventResource;
use App\Models\AcademicCalendarEvent;
use App\Notifications\AcademicCalendarParentNotification;
use App\Services\AcademicCalendarService;
use App\Support\ParentAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class AcademicCalendarController extends Controller
{
    use ResolvesInstitution;

    public function __construct(
        protected AcademicCalendarService $academicCalendarService
    ) {}

    /**
     * Display a listing of calendar events.
     */
    public function index(Request $request)
    {
        $filters = $request->only([
            'academic_year_id',
            'semester_id',
            'event_type',
            'status',
            'start_date',
            'end_date',
            'search',
        ]);

        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        $filters['institution_id'] = $institutionId;

        $perPage = min($request->get('per_page', 15), 100);

        $events = $this->academicCalendarService->list($filters, $perPage);

        return AcademicCalendarEventResource::collection($events);
    }

    /**
     * Store a newly created calendar event.
     */
    public function store(StoreAcademicCalendarEventRequest $request)
    {
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }

        $validated = $request->validated();
        $validated['institution_id'] = $institutionId;
        $validated['created_by'] = $request->user()->id;

        $event = $this->academicCalendarService->create($validated);

        $parents = ParentAccess::parentUsersForInstitution((int) $event->institution_id);
        if ($parents->isNotEmpty()) {
            Notification::send($parents, new AcademicCalendarParentNotification($event));
        }

        return response()->json([
            'message' => 'Event kalender akademik berhasil ditambahkan',
            'data' => new AcademicCalendarEventResource($event->load(['academicYear', 'semester', 'creator'])),
        ], 201);
    }

    /**
     * Display the specified calendar event.
     */
    public function show(Request $request, $id)
    {
        $event = $this->academicCalendarService->find($id);

        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $event->institution_id, 'Event tidak ditemukan atau tidak memiliki akses')) {
            return $resp;
        }

        return new AcademicCalendarEventResource($event);
    }

    /**
     * Update the specified calendar event.
     */
    public function update(UpdateAcademicCalendarEventRequest $request, $id)
    {
        $event = AcademicCalendarEvent::findOrFail($id);

        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $event->institution_id, 'Event tidak ditemukan atau tidak memiliki akses')) {
            return $resp;
        }

        $event = $this->academicCalendarService->update($event, $request->validated());

        return response()->json([
            'message' => 'Event kalender akademik berhasil diperbarui',
            'data' => new AcademicCalendarEventResource($event),
        ]);
    }

    /**
     * Remove the specified calendar event.
     */
    public function destroy(Request $request, $id)
    {
        $event = AcademicCalendarEvent::findOrFail($id);

        if ($resp = $this->denyUnlessCanAccessInstitution($request, (int) $event->institution_id, 'Event tidak ditemukan atau tidak memiliki akses')) {
            return $resp;
        }

        $this->academicCalendarService->delete($event);

        return response()->json([
            'message' => 'Event kalender akademik berhasil dihapus',
        ]);
    }

    /**
     * Get events by date range for calendar view.
     */
    public function calendar(Request $request)
    {
        $request->validate([
            'start_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);
        $institutionId = $this->resolveInstitutionId($request);
        if (!$institutionId) {
            return response()->json(['message' => 'Institusi tidak ditemukan.'], 403);
        }
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $events = $this->academicCalendarService->getByDateRange(
            $institutionId,
            $startDate,
            $endDate
        );

        return AcademicCalendarEventResource::collection($events);
    }

    /**
     * Get upcoming events.
     */
    public function upcoming(Request $request)
    {
        $user = $request->user();
        $institutionId = $this->resolveInstitutionId($request);
        if ($user->isStudent() && $user->studentProfile) {
            $institutionId = $user->studentProfile->institution_id;
        }
        if ($user->isParent()) {
            $child = ParentAccess::linkedStudents($user)->first();
            if ($child) {
                $institutionId = $child->institution_id;
            }
        }
        if (!$institutionId) {
            return AcademicCalendarEventResource::collection(collect());
        }
        $days = min($request->get('days', 30), 90);

        $events = $this->academicCalendarService->getUpcoming($institutionId, $days);

        return AcademicCalendarEventResource::collection($events);
    }
}
