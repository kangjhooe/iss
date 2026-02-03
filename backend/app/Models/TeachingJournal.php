<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class TeachingJournal extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'teaching_journals';

    protected $fillable = [
        'institution_id',
        'semester_id',
        'lesson_schedule_id',
        'class_id',
        'subject_id',
        'employee_id',
        'journal_date',
        'period',
        'material_taught',
        'attendance_notes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'period' => 'integer',
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

    public function lessonSchedule()
    {
        return $this->belongsTo(LessonSchedule::class, 'lesson_schedule_id');
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

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForSemester($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeDateFrom($query, string $date)
    {
        return $query->whereDate('journal_date', '>=', $date);
    }

    public function scopeDateTo($query, string $date)
    {
        return $query->whereDate('journal_date', '<=', $date);
    }

    /**
     * Get the student attendances for this journal session.
     */
    public function studentAttendances()
    {
        return $this->hasMany(StudentAttendance::class, 'teaching_journal_id');
    }
}
