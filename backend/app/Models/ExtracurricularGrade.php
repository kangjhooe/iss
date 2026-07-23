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

    /**
     * Predikat dari skor berdasarkan KKM.
     * Di bawah KKM = D; rentang KKM–100 dibagi 3 untuk C, B, A.
     */
    public static function predicateFromScore(?float $score, float|int|string|null $kkm = 75): ?string
    {
        if ($score === null) {
            return null;
        }

        $kkmValue = $kkm !== null ? (float) $kkm : 75.0;
        if ($kkmValue < 0) {
            $kkmValue = 0;
        }
        if ($kkmValue > 100) {
            $kkmValue = 100;
        }

        if ($score < $kkmValue) {
            return 'D';
        }

        $band = (100 - $kkmValue) / 3;
        if ($band <= 0) {
            return 'A';
        }

        if ($score >= $kkmValue + (2 * $band)) {
            return 'A';
        }
        if ($score >= $kkmValue + $band) {
            return 'B';
        }

        return 'C';
    }
}

