<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonScheduleTemplate extends Model
{
    protected $table = 'lesson_schedule_templates';

    protected $fillable = [
        'institution_id',
        'semester_id',
        'name',
        'days',
    ];

    protected function casts(): array
    {
        return [
            'days' => 'array',
        ];
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function classes()
    {
        return $this->hasMany(SchoolClass::class, 'lesson_schedule_template_id');
    }

    /**
     * Default template: Mon–Fri 8 JP, Sat–Sun holiday.
     *
     * @return array<int, array{day_of_week: int, day_name: string, periods: int, is_holiday: bool}>
     */
    public static function defaultDays(): array
    {
        $days = [];
        foreach (LessonSchedule::DAYS as $day => $name) {
            $isWeekend = $day >= 6;
            $days[] = [
                'day_of_week' => $day,
                'day_name' => $name,
                'periods' => $isWeekend ? 0 : 8,
                'is_holiday' => $isWeekend,
            ];
        }

        return $days;
    }

    /**
     * Normalize & enrich stored days with day_name.
     *
     * @param  array<int, array<string, mixed>>  $days
     * @return array<int, array{day_of_week: int, day_name: string, periods: int, is_holiday: bool}>
     */
    public static function normalizeDays(array $days): array
    {
        $byDay = [];
        foreach ($days as $row) {
            $day = (int) ($row['day_of_week'] ?? 0);
            if (! isset(LessonSchedule::DAYS[$day])) {
                continue;
            }
            $isHoliday = (bool) ($row['is_holiday'] ?? false);
            $periods = $isHoliday ? 0 : max(0, min(20, (int) ($row['periods'] ?? 0)));
            $byDay[$day] = [
                'day_of_week' => $day,
                'day_name' => LessonSchedule::DAYS[$day],
                'periods' => $periods,
                'is_holiday' => $isHoliday || $periods === 0,
            ];
        }

        $normalized = [];
        foreach (LessonSchedule::DAYS as $day => $name) {
            $normalized[] = $byDay[$day] ?? [
                'day_of_week' => $day,
                'day_name' => $name,
                'periods' => 0,
                'is_holiday' => true,
            ];
        }

        return $normalized;
    }

    public function periodsForDay(int $dayOfWeek): int
    {
        foreach ($this->days ?? [] as $row) {
            if ((int) ($row['day_of_week'] ?? 0) === $dayOfWeek) {
                if (! empty($row['is_holiday'])) {
                    return 0;
                }

                return (int) ($row['periods'] ?? 0);
            }
        }

        return 0;
    }

    public function maxPeriods(): int
    {
        $max = 0;
        foreach ($this->days ?? [] as $row) {
            if (! empty($row['is_holiday'])) {
                continue;
            }
            $max = max($max, (int) ($row['periods'] ?? 0));
        }

        return $max;
    }

    /**
     * Active teaching days (not holiday, periods > 0).
     *
     * @return array<int, array{day_of_week: int, day_name: string, periods: int, is_holiday: bool}>
     */
    public function activeDays(): array
    {
        return array_values(array_filter(
            self::normalizeDays($this->days ?? []),
            fn ($d) => ! $d['is_holiday'] && $d['periods'] > 0
        ));
    }
}
