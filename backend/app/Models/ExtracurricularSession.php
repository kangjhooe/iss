<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtracurricularSession extends Model
{
    use SoftDeletes;

    protected $table = 'extracurricular_sessions';

    protected $fillable = [
        'institution_id',
        'extracurricular_id',
        'semester_id',
        'session_date',
        'start_time',
        'end_time',
        'topic',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function extracurricular(): BelongsTo
    {
        return $this->belongsTo(Extracurricular::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ExtracurricularAttendance::class, 'session_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(ExtracurricularSessionGrade::class, 'session_id');
    }
}
