<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAcademicCalendarEventRequest;
use App\Http\Requests\UpdateAcademicCalendarEventRequest;
use App\Http\Resources\AcademicCalendarEventResource;
use App\Models\AcademicCalendarEvent;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;

class AcademicCalendarController extends Controller
{
    public function __construct(
        protected AcademicCalendarService $academicCalendarService
    ) {}

    /**
     * Display a listing of calendar events.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $filters = $request->only([
            'academic_year_id',
            'semester_id',
            'event_type',
            'status',
            'start_date',
            'end_date',
            'search'
        ]);
        
        // Always filter by institution
        $filters['institution_id'] = $user->institution_id;
        
        $perPage = min($request->get('per_page', 15), 100);
        
        $events = $this->academicCalendarService->list($filters, $perPage);

        return AcademicCalendarEventResource::collection($events);
    }

    /**
     * Store a newly created calendar event.
     */
    public function store(StoreAcademicCalendarEventRequest $request)
    {
        $validated = $request->validated();
        $validated['institution_id'] = $request->user()->institution_id;
        $validated['created_by'] = $request->user()->id;

        $event = $this->academicCalendarService->create($validated);

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

        // Check if event belongs to user's institution
        $user = $request->user();
        if ($event->institution_id !== $user->institution_id) {
            return response()->json([
                'message' => 'Event tidak ditemukan atau tidak memiliki akses',
            ], 404);
        }

        return new AcademicCalendarEventResource($event);
    }

    /**
     * Update the specified calendar event.
     */
    public function update(UpdateAcademicCalendarEventRequest $request, $id)
    {
        $event = AcademicCalendarEvent::findOrFail($id);

        // Check if event belongs to user's institution
        $user = $request->user();
        if ($event->institution_id !== $user->institution_id) {
            return response()->json([
                'message' => 'Event tidak ditemukan atau tidak memiliki akses',
            ], 404);
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

        // Check if event belongs to user's institution
        $user = $request->user();
        if ($event->institution_id !== $user->institution_id) {
            return response()->json([
                'message' => 'Event tidak ditemukan atau tidak memiliki akses',
            ], 404);
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
        $user = $request->user();
        $startDate = $request->get('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->endOfMonth()->format('Y-m-d'));

        $events = $this->academicCalendarService->getByDateRange(
            $user->institution_id,
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
        $days = min($request->get('days', 30), 90);

        $events = $this->academicCalendarService->getUpcoming($user->institution_id, $days);

        return AcademicCalendarEventResource::collection($events);
    }
}
