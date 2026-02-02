<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasFactory;

    protected $table = 'achievements';

    protected $fillable = [
        'institution_id',
        'student_id',
        'achievement_type_id',
        'given_by',
        'achievement_date',
        'point_value',
        'notes',
        'academic_year_id',
        'semester_id',
    ];

    protected function casts(): array
    {
        return [
            'achievement_date' => 'date',
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

    public function achievementType()
    {
        return $this->belongsTo(AchievementType::class, 'achievement_type_id');
    }

    public function giver()
    {
        return $this->belongsTo(User::class, 'given_by');
    }

    /**
     * Get the academic year for this achievement.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    /**
     * Get the semester for this achievement.
     */
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
