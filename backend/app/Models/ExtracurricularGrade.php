<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtracurricularGrade extends Model
{
    protected $table = 'extracurricular_grades';

    protected $fillable = [
        'institution_id',
        'extracurricular_id',
        'student_id',
        'semester_id',
        'academic_year_id',
        'score',
        'predicate',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by');
    }

    public static function predicateFromScore(?float $score): ?string
    {
        if ($score === null) {
            return null;
        }
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }

        return 'D';
    }
}
