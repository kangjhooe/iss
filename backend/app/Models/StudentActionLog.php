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
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'date',
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

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
