<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PpdbPeriod extends Model
{
    protected $table = 'ppdb_periods';

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'name',
        'level',
        'open_date',
        'close_date',
        're_registration_deadline',
        'status',
        'description',
        'registration_fee',
        're_registration_fee',
    ];

    protected function casts(): array
    {
        return [
            'open_date' => 'date',
            'close_date' => 'date',
            're_registration_deadline' => 'date',
            'registration_fee' => 'decimal:2',
            're_registration_fee' => 'decimal:2',
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(PpdbApplicant::class, 'ppdb_period_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}
