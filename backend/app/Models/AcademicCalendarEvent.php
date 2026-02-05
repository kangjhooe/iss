<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class AcademicCalendarEvent extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'academic_calendar_events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'semester_id',
        'title',
        'description',
        'event_type',
        'start_date',
        'end_date',
        'is_all_day',
        'start_time',
        'end_time',
        'reminder_days_before',
        'color',
        'status',
        'reminder_sent_at',
        'created_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_all_day' => 'boolean',
            'reminder_days_before' => 'array',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * Get the institution that owns this event.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the academic year for this event.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the semester for this event.
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Get the user who created this event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope a query to filter by academic year.
     */
    public function scopeForAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope a query to filter by semester.
     */
    public function scopeForSemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    /**
     * Scope a query to filter by event type.
     */
    public function scopeByEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where(function($q3) use ($endDate) {
                         $q3->whereNull('end_date')
                            ->orWhere('end_date', '>=', $endDate);
                     });
              });
        });
    }

    /**
     * Scope a query to get upcoming events.
     */
    public function scopeUpcoming($query, int $days = 30)
    {
        return $query->where('start_date', '>=', now()->toDateString())
                     ->where('start_date', '<=', now()->addDays($days)->toDateString())
                     ->where('status', 'Aktif')
                     ->orderBy('start_date', 'asc');
    }

    /**
     * Check if reminder should be sent.
     */
    public function shouldSendReminder(): bool
    {
        if (!$this->reminder_days_before || empty($this->reminder_days_before)) {
            return false;
        }

        if ($this->status !== 'Aktif') {
            return false;
        }

        $daysUntilEvent = now()->diffInDays($this->start_date, false);
        
        if ($daysUntilEvent < 0) {
            return false; // Event sudah lewat
        }

        // Check if reminder already sent for this day
        $lastReminderSent = $this->reminder_sent_at 
            ? now()->diffInDays($this->reminder_sent_at, false) 
            : null;

        foreach ($this->reminder_days_before as $daysBefore) {
            if ($daysUntilEvent == $daysBefore) {
                // Check if we haven't sent reminder for this specific day
                if ($lastReminderSent === null || $lastReminderSent > $daysBefore) {
                    return true;
                }
            }
        }

        return false;
    }
}
