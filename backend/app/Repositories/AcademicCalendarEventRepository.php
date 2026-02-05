<?php

namespace App\Repositories;

use App\Models\AcademicCalendarEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AcademicCalendarEventRepository extends BaseRepository
{
    /**
     * Get the model class name.
     */
    protected function model(): string
    {
        return AcademicCalendarEvent::class;
    }

    /**
     * Get list of calendar events with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->query();

        // Apply filters
        if (isset($filters['institution_id'])) {
            $query->forInstitution($filters['institution_id']);
        }

        if (isset($filters['academic_year_id'])) {
            $query->forAcademicYear($filters['academic_year_id']);
        }

        if (isset($filters['semester_id'])) {
            $query->forSemester($filters['semester_id']);
        }

        if (isset($filters['event_type'])) {
            $query->byEventType($filters['event_type']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->byDateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $perPage = min($perPage, 100);

        return $query->with(['academicYear', 'semester', 'creator'])
            ->orderBy('start_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate($perPage);
    }

    /**
     * Get calendar event with relationships.
     */
    public function findWithRelations(int $id): AcademicCalendarEvent
    {
        return $this->query()
            ->with(['institution', 'academicYear', 'semester', 'creator'])
            ->findOrFail($id);
    }

    /**
     * Get upcoming events for reminder.
     */
    public function getUpcomingForReminder(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query()
            ->where('status', 'Aktif')
            ->whereNotNull('reminder_days_before')
            ->where('start_date', '>=', now()->toDateString())
            ->where('start_date', '<=', now()->addDays($days)->toDateString())
            ->with(['institution', 'academicYear', 'semester'])
            ->get()
            ->filter(function($event) {
                return $event->shouldSendReminder();
            });
    }

    /**
     * Get events by date range for calendar view.
     */
    public function getByDateRange(int $institutionId, $startDate, $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return $this->query()
            ->forInstitution($institutionId)
            ->byDateRange($startDate, $endDate)
            ->where('status', 'Aktif')
            ->with(['academicYear', 'semester'])
            ->orderBy('start_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }
}
