<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use Illuminate\Database\Seeder;

/**
 * Jadwal Penggunaan Lab (lesson_schedules) — tampil di tab Jadwal Lab.
 *
 * Variasi 6 mapel (bukan Coding saja), 6 kelas TA 2026/2027, Ganjil + Genap.
 *
 *   php artisan db:seed --class=MtsAlFalahLabScheduleSeeder
 */
class MtsAlFalahLabScheduleSeeder extends Seeder
{
    public const NPSN = '10816663';

    public const SEED_MARKER = 'SEED:LAB-SCHEDULE-ALFALAH';

    public function run(): void
    {
        $institution = Institution::where('npsn', self::NPSN)->first();
        if (! $institution) {
            $this->command?->error('Institution NPSN '.self::NPSN.' tidak ditemukan.');

            return;
        }

        $room = Room::where('institution_id', $institution->id)
            ->where('type', 'Laboratorium')
            ->where('lab_type', 'Komputer')
            ->first();
        if (! $room) {
            $this->command?->error('Ruang Lab Komputer belum ada.');

            return;
        }

        $employees = [
            'rudi' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Rudi%')->first(),
            'enny' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Enny%')->first(),
            'lida' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Lida%')->first(),
            'nurpenda' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Nurpenda%')->first(),
            'sumardi' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Sumardi%')->first(),
            'novariza' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Novariza%')->first(),
        ];

        $subjects = Subject::where('institution_id', $institution->id)
            ->whereIn('code', ['COD', 'MTK', 'IPA', 'IPS', 'FQH', 'AAQ', 'INF', 'AQH', 'SKI'])
            ->get()
            ->keyBy('code');

        foreach (['rudi', 'enny', 'lida', 'nurpenda', 'sumardi', 'novariza'] as $key) {
            if (! $employees[$key]) {
                $this->command?->error("Guru '{$key}' tidak ditemukan.");

                return;
            }
        }
        foreach (['COD', 'MTK', 'IPA', 'IPS', 'FQH', 'AAQ'] as $code) {
            if (! $subjects->has($code)) {
                $this->command?->error("Mapel {$code} tidak ditemukan.");

                return;
            }
        }

        $classNames = ['VII-A', 'VII-B', 'VIII-A', 'VIII-B', 'IX-A', 'IX-B'];
        $classes = SchoolClass::where('institution_id', $institution->id)
            ->where('academic_year_id', 2)
            ->whereIn('name', $classNames)
            ->get()
            ->keyBy('name');

        foreach ($classNames as $name) {
            if (! $classes->has($name)) {
                $this->command?->error("Kelas {$name} tidak ditemukan.");

                return;
            }
        }

        $semesters = Semester::where('academic_year_id', 2)->orderBy('id')->get();
        if ($semesters->count() < 2) {
            $this->command?->error('Semester TA 2026/2027 tidak lengkap.');

            return;
        }

        /**
         * 6 slot × 6 mapel berbeda (2 JP). Slot menghindari bentrok jadwal kelas existing.
         *
         * @var list<array{class:string,day:int,periods:list<int>,subject:string,teacher:string}>
         */
        $weekly = [
            ['class' => 'VII-B',  'day' => 1, 'periods' => [1, 2], 'subject' => 'COD', 'teacher' => 'rudi'],      // Coding
            ['class' => 'VII-A',  'day' => 2, 'periods' => [1, 2], 'subject' => 'MTK', 'teacher' => 'nurpenda'], // Matematika
            ['class' => 'VIII-B', 'day' => 3, 'periods' => [4, 5], 'subject' => 'IPA', 'teacher' => 'lida'],     // IPA
            ['class' => 'VIII-A', 'day' => 4, 'periods' => [1, 2], 'subject' => 'IPS', 'teacher' => 'sumardi'],  // IPS
            ['class' => 'IX-B',   'day' => 4, 'periods' => [3, 4], 'subject' => 'FQH', 'teacher' => 'novariza'], // Fiqih
            ['class' => 'IX-A',   'day' => 5, 'periods' => [4, 5], 'subject' => 'AAQ', 'teacher' => 'enny'],     // Akidah Akhlaq
        ];

        LessonSchedule::withTrashed()
            ->where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('notes', 'like', '%'.self::SEED_MARKER.'%')
            ->forceDelete();

        $created = 0;
        $usedSubjects = [];

        foreach ($semesters as $semester) {
            foreach ($weekly as $slot) {
                $class = $classes[$slot['class']];
                $subject = $subjects[$slot['subject']];
                $teacher = $employees[$slot['teacher']];
                $usedSubjects[$subject->code] = $subject->name;

                foreach ($slot['periods'] as $period) {
                    $exists = LessonSchedule::withTrashed()
                        ->where('semester_id', $semester->id)
                        ->where('class_id', $class->id)
                        ->where('day_of_week', $slot['day'])
                        ->where('period', $period)
                        ->exists();

                    if ($exists) {
                        $this->command?->warn(sprintf(
                            'Skip %s %s D%d P%d (slot sudah terisi).',
                            $semester->name,
                            $class->name,
                            $slot['day'],
                            $period
                        ));
                        continue;
                    }

                    LessonSchedule::create([
                        'institution_id' => $institution->id,
                        'semester_id' => $semester->id,
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'employee_id' => $teacher->id,
                        'room_id' => $room->id,
                        'day_of_week' => $slot['day'],
                        'period' => $period,
                        'start_time' => null,
                        'end_time' => null,
                        'notes' => self::SEED_MARKER,
                    ]);
                    $created++;
                }
            }
        }

        $this->command?->info(sprintf(
            'Lab schedule seeded: %d slot, %d mapel → room #%d. Refresh halaman Lab.',
            $created,
            count($usedSubjects),
            $room->id
        ));
        foreach ($usedSubjects as $code => $name) {
            $this->command?->line("  - {$code}: {$name}");
        }
    }
}
