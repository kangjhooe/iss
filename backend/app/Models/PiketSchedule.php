<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PiketSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'piket_schedules';

    public const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ];

    public const SHIFTS = [
        'pagi' => 'Pagi',
        'siang' => 'Siang',
        'full' => 'Seharian',
    ];

    protected $fillable = [
        'institution_id',
        'academic_year_id',
        'semester_id',
        'employee_id',
        'day_of_week',
        'shift',
        'start_time',
        'end_time',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function logs()
    {
        return $this->hasMany(PiketLog::class, 'piket_schedule_id');
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? (string) $this->day_of_week;
    }

    public function getShiftLabelAttribute(): string
    {
        return self::SHIFTS[$this->shift] ?? $this->shift;
    }
}
