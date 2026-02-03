<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentCountSnapshot extends Model
{
    protected $table = 'student_count_snapshots';

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'year',
        'month',
        'grade',
        'male',
        'female',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'grade' => 'integer',
            'male' => 'integer',
            'female' => 'integer',
            'total' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
