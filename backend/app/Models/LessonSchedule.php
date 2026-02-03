<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class LessonSchedule extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lesson_schedules';

    const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
    ];

    protected $fillable = [
        'institution_id',
        'semester_id',
        'class_id',
        'subject_id',
        'employee_id',
        'room_id',
        'day_of_week',
        'period',
        'start_time',
        'end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'period' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the teaching journals for this lesson schedule.
     */
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'lesson_schedule_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForSemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForRoom($query, int $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public static function getDayName(int $dayOfWeek): string
    {
        return self::DAYS[$dayOfWeek] ?? '';
    }
}
