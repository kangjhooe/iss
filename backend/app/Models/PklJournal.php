<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PklJournal extends Model
{
    public const STATUSES = ['draft', 'submitted'];

    protected $fillable = [
        'institution_id',
        'pkl_placement_id',
        'student_id',
        'journal_date',
        'activities',
        'hours',
        'status',
        'supervisor_notes',
    ];

    protected function casts(): array
    {
        return [
            'journal_date' => 'date',
            'hours' => 'decimal:1',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function placement()
    {
        return $this->belongsTo(PklPlacement::class, 'pkl_placement_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
