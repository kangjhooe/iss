<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeWeight extends Model
{
    use HasFactory;

    protected $table = 'grade_weights';

    public const DEFAULT_WEIGHT_PENILAIAN = 40.0;
    public const DEFAULT_WEIGHT_UTS = 30.0;
    public const DEFAULT_WEIGHT_UAS = 30.0;
    public const DEFAULT_ASSESSMENT_COUNT = 1;

    protected $fillable = [
        'institution_id',
        'class_id',
        'subject_id',
        'semester_id',
        'weight_penilaian',
        'weight_uts',
        'weight_uas',
        'assessment_count',
        'deadline_penilaian',
        'deadline_uts',
        'deadline_uas',
        'deadline_nilai_akhir',
        'set_by_employee_id',
    ];

    protected function casts(): array
    {
        return [
            'weight_penilaian' => 'decimal:2',
            'weight_uts' => 'decimal:2',
            'weight_uas' => 'decimal:2',
            'assessment_count' => 'integer',
            'deadline_penilaian' => 'date',
            'deadline_uts' => 'date',
            'deadline_uas' => 'date',
            'deadline_nilai_akhir' => 'date',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function setByEmployee()
    {
        return $this->belongsTo(Employee::class, 'set_by_employee_id');
    }

    public static function defaults(): array
    {
        return [
            'weight_penilaian' => self::DEFAULT_WEIGHT_PENILAIAN,
            'weight_uts' => self::DEFAULT_WEIGHT_UTS,
            'weight_uas' => self::DEFAULT_WEIGHT_UAS,
            'assessment_count' => self::DEFAULT_ASSESSMENT_COUNT,
        ];
    }

    public function toPayload(): array
    {
        return [
            'penilaian' => (float) $this->weight_penilaian,
            'uts' => (float) $this->weight_uts,
            'uas' => (float) $this->weight_uas,
            'assessment_count' => max(1, (int) $this->assessment_count),
            'deadline_penilaian' => optional($this->deadline_penilaian)?->format('Y-m-d'),
            'deadline_uts' => optional($this->deadline_uts)?->format('Y-m-d'),
            'deadline_uas' => optional($this->deadline_uas)?->format('Y-m-d'),
            'deadline_nilai_akhir' => optional($this->deadline_nilai_akhir)?->format('Y-m-d'),
            'set' => true,
        ];
    }
}
