<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabSchedule extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $table = 'lab_schedules';

    public const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    protected $fillable = [
        'institution_id',
        'room_id',
        'semester_id',
        'class_id',
        'subject_id',
        'employee_id',
        'day_of_week',
        'period',
        'duration',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'period' => 'integer',
            'duration' => 'integer',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
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

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function periodEnd(): int
    {
        return (int) $this->period + max(1, (int) $this->duration) - 1;
    }

    public function periodLabel(): string
    {
        $start = (int) $this->period;
        $end = $this->periodEnd();

        return $start === $end ? (string) $start : "{$start}–{$end}";
    }

    public static function getDayName(int $dayOfWeek): string
    {
        return self::DAYS[$dayOfWeek] ?? '';
    }

    public function coversPeriod(int $period): bool
    {
        return $period >= (int) $this->period && $period <= $this->periodEnd();
    }
}
