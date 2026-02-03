<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Grade extends Model
{
    use HasFactory, Auditable;

    protected $table = 'grades';

    public const TYPE_UH = 'uh';
    public const TYPE_UTS = 'uts';
    public const TYPE_UAS = 'uas';
    public const TYPE_TUGAS = 'tugas';
    public const TYPE_NILAI_AKHIR = 'nilai_akhir';

    public const TYPES = [
        self::TYPE_UH => 'UH',
        self::TYPE_UTS => 'UTS',
        self::TYPE_UAS => 'UAS',
        self::TYPE_TUGAS => 'Tugas',
        self::TYPE_NILAI_AKHIR => 'Nilai Akhir',
    ];

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'semester_id',
        'class_id',
        'subject_id',
        'student_id',
        'employee_id',
        'grade_type',
        'value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
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

    public function student()
    {
        return $this->belongsTo(Student::class);
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

    public function scopeForClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public static function getTypeLabel(string $type): string
    {
        return self::TYPES[$type] ?? $type;
    }
}
