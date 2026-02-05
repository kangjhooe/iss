<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Semester extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'semesters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'academic_year_id',
        'name',
        'order',
        'start_date',
        'end_date',
        'status',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * Get the academic year that owns this semester.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the lesson schedules for this semester.
     */
    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class);
    }

    /**
     * Get the classes for this semester.
     */
    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'semester_id');
    }

    /**
     * Get the students for this semester.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'semester_id');
    }

    /**
     * Get the violations for this semester.
     */
    public function violations()
    {
        return $this->hasMany(Violation::class, 'semester_id');
    }

    /**
     * Get the achievements for this semester.
     */
    public function achievements()
    {
        return $this->hasMany(Achievement::class, 'semester_id');
    }

    /**
     * Get the class student history for this semester.
     */
    public function classStudentHistory()
    {
        return $this->hasMany(ClassStudentHistory::class, 'semester_id');
    }

    /**
     * Get the counseling sessions for this semester.
     */
    public function counselingSessions()
    {
        return $this->hasMany(CounselingSession::class, 'semester_id');
    }

    /**
     * Get the grades (nilai) for this semester.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class, 'semester_id');
    }

    /**
     * Get the teaching journals for this semester.
     */
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'semester_id');
    }

    /**
     * Get the academic calendar events for this semester.
     */
    public function academicCalendarEvents()
    {
        return $this->hasMany(AcademicCalendarEvent::class, 'semester_id');
    }

    /**
     * Scope a query to only include active semesters.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    /**
     * Scope a query to filter by academic year.
     */
    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Scope a query to filter by name (Ganjil/Genap).
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', $name);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Get the current active semester.
     */
    public static function getCurrent()
    {
        return static::active()->first();
    }

    /**
     * Get the current active semester for a specific academic year.
     */
    public static function getCurrentForAcademicYear($academicYearId)
    {
        return static::where('academic_year_id', $academicYearId)
            ->active()
            ->first();
    }

    /**
     * Check if this semester is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'Aktif';
    }

    /**
     * Check if this semester is current (based on date).
     */
    public function isCurrent(): bool
    {
        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }
}
