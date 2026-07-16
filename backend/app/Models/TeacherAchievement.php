<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherAchievement extends Model
{
    use HasFactory;

    protected $table = 'teacher_achievements';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const LEVELS = [
        'sekolah',
        'kabupaten',
        'provinsi',
        'nasional',
        'internasional',
    ];

    protected $fillable = [
        'institution_id',
        'employee_id',
        'achievement_type_id',
        'title',
        'achievement_date',
        'point_value',
        'level',
        'notes',
        'evidence_path',
        'status',
        'submitted_by',
        'given_by',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
        'academic_year_id',
        'semester_id',
    ];

    protected function casts(): array
    {
        return [
            'achievement_date' => 'date',
            'reviewed_at' => 'datetime',
            'point_value' => 'integer',
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

    public function achievementType()
    {
        return $this->belongsTo(TeacherAchievementType::class, 'achievement_type_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function giver()
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
