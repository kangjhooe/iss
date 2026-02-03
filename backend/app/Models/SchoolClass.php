<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class SchoolClass extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'class';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'room_id',
        'teacher_id',
        'code',
        'name',
        'grade',
        'academic_year',
        'academic_year_id',
        'semester_id',
        'capacity',
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
            'grade' => 'integer',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get the institution that owns the class.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the room assigned to this class.
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Get the employee (wali kelas) assigned to this class.
     */
    public function teacher()
    {
        return $this->belongsTo(Employee::class, 'teacher_id');
    }

    /**
     * Get the students in this class.
     * Explicit foreign key: student.class_id -> class.id (bukan school_class_id).
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id', 'id');
    }

    /**
     * Get the class student history records.
     * FK di class_student_history: class_id -> class.id
     */
    public function studentHistory()
    {
        return $this->hasMany(ClassStudentHistory::class, 'class_id');
    }

    /**
     * Get the academic year for this class.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get the semester for this class.
     */
    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    /**
     * Get the lesson schedules for this class.
     */
    public function lessonSchedules()
    {
        return $this->hasMany(LessonSchedule::class, 'class_id');
    }

    /**
     * Get the counseling sessions for this class.
     */
    public function counselingSessions()
    {
        return $this->hasMany(CounselingSession::class, 'class_id');
    }

    /**
     * Get the grades (nilai) for this class.
     */
    public function grades()
    {
        return $this->hasMany(Grade::class, 'class_id');
    }

    /**
     * Get the teaching journals for this class.
     */
    public function teachingJournals()
    {
        return $this->hasMany(TeachingJournal::class, 'class_id');
    }

    /**
     * Scope a query to only include active classes.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Aktif');
    }

    /**
     * Scope a query to filter by semester ID.
     */
    public function scopeBySemesterId($query, int $semesterId)
    {
        return $query->where('semester_id', $semesterId);
    }

    /**
     * Scope a query to filter by institution.
     */
    public function scopeForInstitution($query, $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope a query to filter by academic year.
     */
    public function scopeByAcademicYear($query, string $academicYear)
    {
        return $query->where('academic_year', $academicYear);
    }

    /**
     * Scope a query to filter by grade.
     */
    public function scopeByGrade($query, int $grade)
    {
        return $query->where('grade', $grade);
    }

    /**
     * Get the count of students in this class.
     */
    public function getStudentsCountAttribute(): int
    {
        return $this->students()->count();
    }

    /**
     * Check if class has available capacity.
     */
    public function hasAvailableCapacity(): bool
    {
        if ($this->capacity === null) {
            return true; // No capacity limit
        }
        return $this->students()->count() < $this->capacity;
    }

    /**
     * Get available capacity.
     */
    public function getAvailableCapacityAttribute(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }
        return max(0, $this->capacity - $this->students()->count());
    }
}
