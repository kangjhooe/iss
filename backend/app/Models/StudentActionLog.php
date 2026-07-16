<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentActionLog extends Model
{
    use HasFactory;

    protected $table = 'student_action_logs';

    protected $fillable = [
        'institution_id',
        'student_id',
        'point_threshold_id',
        'action_name',
        'action_date',
        'recorded_by',
        'notes',
        'score_at_action',
        'academic_year_id',
        'semester_id',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'date',
            'score_at_action' => 'integer',
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

    public function pointThreshold()
    {
        return $this->belongsTo(PointThreshold::class, 'point_threshold_id');
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
