<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeRemedial extends Model
{
    use HasFactory;

    protected $table = 'grade_remedials';

    public const TYPE_REMEDIAL = 'remedial';
    public const TYPE_PENGAYAAN = 'pengayaan';

    public const STATUS_PLANNED = 'planned';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPES = [
        self::TYPE_REMEDIAL => 'Remidi',
        self::TYPE_PENGAYAAN => 'Pengayaan',
    ];

    public const STATUSES = [
        self::STATUS_PLANNED => 'Direncanakan',
        self::STATUS_COMPLETED => 'Selesai',
        self::STATUS_CANCELLED => 'Dibatalkan',
    ];

    protected $fillable = [
        'institution_id',
        'semester_id',
        'class_id',
        'subject_id',
        'student_id',
        'employee_id',
        'type',
        'source_grade_type',
        'original_value',
        'kkm_snapshot',
        'scheduled_date',
        'completed_date',
        'remedial_value',
        'status',
        'apply_to_grade',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'original_value' => 'decimal:2',
            'kkm_snapshot' => 'decimal:2',
            'remedial_value' => 'decimal:2',
            'scheduled_date' => 'date',
            'completed_date' => 'date',
            'apply_to_grade' => 'boolean',
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

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
