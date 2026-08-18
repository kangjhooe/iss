<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UksVisit extends Model
{
    use HasFactory;

    protected $table = 'uks_visits';

    protected $fillable = [
        'institution_id',
        'student_id',
        'recorded_by',
        'uks_visit_type_id',
        'visit_date',
        'status',
        'complaint',
        'action_taken',
        'notes',
        'height_cm',
        'weight_kg',
        'blood_pressure',
        'temperature_c',
        'academic_year_id',
        'semester_id',
        'class_id',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'height_cm' => 'float',
            'weight_kg' => 'float',
            'temperature_c' => 'float',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function visitType()
    {
        return $this->belongsTo(UksVisitType::class, 'uks_visit_type_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
