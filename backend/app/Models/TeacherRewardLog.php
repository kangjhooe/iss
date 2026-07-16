<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherRewardLog extends Model
{
    use HasFactory;

    protected $table = 'teacher_reward_logs';

    protected $fillable = [
        'institution_id',
        'employee_id',
        'teacher_point_reward_id',
        'reward_name',
        'reward_date',
        'score_at_reward',
        'notes',
        'recorded_by',
        'academic_year_id',
        'semester_id',
    ];

    protected function casts(): array
    {
        return [
            'reward_date' => 'date',
            'score_at_reward' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reward()
    {
        return $this->belongsTo(TeacherPointReward::class, 'teacher_point_reward_id');
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

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }
}
