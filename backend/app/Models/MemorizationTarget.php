<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationTarget extends Model
{
    public const SCOPE_EXTRACURRICULAR = 'extracurricular';
    public const SCOPE_CLASS = 'class';
    public const SCOPE_STUDENT = 'student';

    protected $table = 'memorization_targets';

    protected $fillable = [
        'institution_id',
        'extracurricular_id',
        'scope',
        'class_id',
        'student_id',
        'semester_id',
        'academic_year_id',
        'name',
        'items',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
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
}
