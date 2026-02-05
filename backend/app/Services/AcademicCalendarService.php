<?php

namespace App\Services;

use App\Models\AcademicCalendarEvent;
use App\Repositories\AcademicCalendarEventRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AcademicCalendarService
{
    public function __construct(
        protected AcademicCalendarEventRepository $repository
    ) {}

    /**
     * Get list of calendar events with filters.
     */
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->list($filters, $perPage);
    }

    /**
     * Create a new calendar event.
     */
    public function create(array $data): AcademicCalendarEvent
    {
        // Validate date range
        if (isset($data['end_date']) && $data['start_date'] > $data['end_date']) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.'
            ]);
        }

        // Validate time if not all day
        if (isset($data['is_all_day']) && !$data['is_all_day']) {
            if (empty($data['start_time']) || empty($data['end_time'])) {
                throw ValidationException::withMessages([
                    'start_time' => 'Waktu mulai dan akhir wajib diisi untuk event yang bukan seharian.',
                    'end_time' => 'Waktu mulai dan akhir wajib diisi untuk event yang bukan seharian.',
                ]);
            }

            if ($data['start_time'] >= $data['end_time']) {
                throw ValidationException::withMessages([
                    'end_time' => 'Waktu akhir harus setelah waktu mulai.'
                ]);
            }
        }

        // Validate reminder_days_before format
        if (isset($data['reminder_days_before']) && !is_array($data['reminder_days_before'])) {
            throw ValidationException::withMessages([
                'reminder_days_before' => 'Reminder days harus berupa array.'
            ]);
        }

        // Set default end_date if not provided
        if (!isset($data['end_date'])) {
            $data['end_date'] = $data['start_date'];
        }

        // Set default status
        if (!isset($data['status'])) {
            $data['status'] = 'Aktif';
        }

        $event = $this->repository->create($data);

        Log::info('Academic calendar event created', [
            'event_id' => $event->id,
            'title' => $event->title,
            'institution_id' => $event->institution_id,
        ]);

        return $event;
    }

    /**
     * Get calendar event by ID.
     */
    public function find(int $id): AcademicCalendarEvent
    {
        return $this->repository->findWithRelations($id);
    }

    /**
     * Update calendar event.
     */
    public function update(AcademicCalendarEvent $event, array $data): AcademicCalendarEvent
    {
        $startDate = isset($data['start_date']) ? Carbon::parse($data['start_date'])->format('Y-m-d') : $event->start_date?->format('Y-m-d');
        $endDate = isset($data['end_date']) ? Carbon::parse($data['end_date'])->format('Y-m-d') : $event->end_date?->format('Y-m-d');

        if ($startDate && $endDate && $endDate < $startDate) {
            throw ValidationException::withMessages([
                'end_date' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.'
            ]);
        }

        $isAllDay = $data['is_all_day'] ?? $event->is_all_day;
        if (!$isAllDay) {
            $startTime = $data['start_time'] ?? ($event->start_time ? (is_string($event->start_time) ? substr($event->start_time, 0, 5) : $event->start_time) : null);
            $endTime = $data['end_time'] ?? ($event->end_time ? (is_string($event->end_time) ? substr($event->end_time, 0, 5) : $event->end_time) : null);
            if (empty($startTime) || empty($endTime)) {
                throw ValidationException::withMessages([
                    'start_time' => 'Waktu mulai dan akhir wajib diisi untuk event yang bukan seharian.',
                    'end_time' => 'Waktu mulai dan akhir wajib diisi untuk event yang bukan seharian.',
                ]);
            }
            if ($startTime >= $endTime) {
                throw ValidationException::withMessages([
                    'end_time' => 'Waktu akhir harus setelah waktu mulai.'
                ]);
            }
        }

        $this->repository->update($event, $data);

        Log::info('Academic calendar event updated', [
            'event_id' => $event->id,
        ]);

        return $event->fresh(['academicYear', 'semester', 'creator']);
    }

    /**
     * Delete calendar event (soft delete).
     */
    public function delete(AcademicCalendarEvent $event): bool
    {
        $eventId = $event->id;
        $result = $this->repository->delete($event);

        Log::info('Academic calendar event deleted', [
            'event_id' => $eventId,
        ]);

        return $result;
    }

    /**
     * Get events by date range for calendar view.
     */
    public function getByDateRange(int $institutionId, $startDate, $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->getByDateRange($institutionId, $startDate, $endDate);
    }

    /**
     * Get upcoming events.
     */
    public function getUpcoming(int $institutionId, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->query()
            ->forInstitution($institutionId)
            ->upcoming($days)
            ->with(['academicYear', 'semester'])
            ->get();
    }
}
