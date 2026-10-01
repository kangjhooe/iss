<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\LabBooking;
use App\Models\LabUsageJournal;
use App\Models\LessonSchedule;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Jurnal pemakaian Lab Komputer — mengikuti jadwal tetap (lesson_schedules).
 * Hanya tanggal yang sudah lewat (semester Ganjil 2026/2027 s/d hari ini).
 *
 *   php artisan db:seed --class=MtsAlFalahLabJournalSeeder
 */
class MtsAlFalahLabJournalSeeder extends Seeder
{
    public const NPSN = '10816663';

    public const SEED_MARKER = 'SEED:LAB-JOURNAL-ALFALAH';

    /** Estimasi jam per blok period. */
    private const PERIOD_TIMES = [
        1 => ['07:30:00', '08:10:00'],
        2 => ['08:10:00', '08:50:00'],
        3 => ['09:00:00', '09:40:00'],
        4 => ['09:40:00', '10:20:00'],
        5 => ['10:30:00', '11:10:00'],
    ];

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

        $admin = User::where('institution_id', $institution->id)->orderBy('id')->first()
            ?? User::orderBy('id')->first();
        if (! $admin) {
            $this->command?->error('User admin tidak ditemukan.');

            return;
        }

        // Ambil slot "awal blok" (period terkecil per class+day+subject) semester aktif Ganjil
        $semesterId = (int) ($institution->active_semester_id ?: 3);

        $schedules = LessonSchedule::with(['schoolClass', 'subject', 'employee'])
            ->where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('semester_id', $semesterId)
            ->where('notes', 'like', '%SEED:LAB-SCHEDULE-ALFALAH%')
            ->orderBy('day_of_week')
            ->orderBy('period')
            ->get();

        if ($schedules->isEmpty()) {
            $this->command?->error('Jadwal lab seed belum ada. Jalankan MtsAlFalahLabScheduleSeeder dulu.');

            return;
        }

        // Group jadi 1 jurnal per sesi (gabung 2 JP)
        $blocks = $schedules
            ->groupBy(fn ($s) => $s->day_of_week.'-'.$s->class_id.'-'.$s->subject_id)
            ->map(function ($group) {
                $first = $group->sortBy('period')->first();
                $last = $group->sortByDesc('period')->first();
                $startPeriod = (int) $first->period;
                $endPeriod = (int) $last->period;

                return [
                    'schedule' => $first,
                    'day' => (int) $first->day_of_week,
                    'start_time' => self::PERIOD_TIMES[$startPeriod][0] ?? '07:30:00',
                    'end_time' => self::PERIOD_TIMES[$endPeriod][1] ?? '09:30:00',
                ];
            })
            ->values();

        $employeeUsers = [];
        foreach ($schedules->pluck('employee_id')->unique() as $empId) {
            $emp = Employee::find($empId);
            if (! $emp) {
                continue;
            }
            $user = User::where('email', $emp->email)->first() ?? $admin;
            $employeeUsers[$empId] = $user;
        }

        LabUsageJournal::withTrashed()
            ->where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('notes', 'like', '%'.self::SEED_MARKER.'%')
            ->forceDelete();

        $semester = $schedules->first()->semester;
        $rangeStart = Carbon::parse($semester?->start_date ?? '2026-07-01')->startOfDay();
        $rangeEnd = Carbon::today()->endOfDay();
        if ($semester?->end_date) {
            $semEnd = Carbon::parse($semester->end_date)->endOfDay();
            if ($semEnd->lt($rangeEnd)) {
                $rangeEnd = $semEnd;
            }
        }

        $skipRanges = [
            [Carbon::parse('2026-12-21'), Carbon::parse('2026-12-31')],
        ];

        $activities = [
            'COD' => 'Praktik coding / pemrograman di lab',
            'MTK' => 'Latihan soal Matematika berbantuan komputer',
            'IPA' => 'Simulasi / pencarian materi IPA di lab',
            'IPS' => 'Presentasi & riset materi IPS',
            'FQH' => 'Pembelajaran Fiqih multimedia',
            'AAQ' => 'Pembelajaran Akidah Akhlaq berbantuan media',
        ];

        $created = 0;
        $incidentEvery = 12; // sesekali catat insiden
        $weekIndex = 0;

        foreach ($blocks as $block) {
            $schedule = $block['schedule'];
            $cursor = $rangeStart->copy();
            if ($cursor->dayOfWeek !== $block['day']) {
                $cursor->next($block['day']);
            }

            $localWeek = 0;
            while ($cursor->lte($rangeEnd)) {
                if ($this->inSkipRanges($cursor, $skipRanges)) {
                    $cursor->addWeek();
                    continue;
                }

                $subjectCode = $schedule->subject?->code ?? '';
                $activity = $activities[$subjectCode]
                    ?? ('Penggunaan lab — '.$schedule->subject?->name);

                $participants = 22 + (($localWeek + $schedule->class_id) % 8); // 22–29

                $incident = null;
                if (($weekIndex % $incidentEvery) === 7) {
                    $incident = 'Satu unit laptop hang saat praktikum; diganti unit cadangan.';
                } elseif (($weekIndex % $incidentEvery) === 11) {
                    $incident = 'Proyektor sempat mati; dinyalakan ulang, lanjut normal.';
                }

                $recorder = $employeeUsers[$schedule->employee_id] ?? $admin;

                $booking = LabBooking::where('institution_id', $institution->id)
                    ->where('room_id', $room->id)
                    ->whereDate('date', $cursor->toDateString())
                    ->where('status', 'approved')
                    ->where('notes', 'like', '%subject='.$subjectCode.'%')
                    ->first();

                LabUsageJournal::create([
                    'institution_id' => $institution->id,
                    'room_id' => $room->id,
                    'date' => $cursor->toDateString(),
                    'start_time' => $block['start_time'],
                    'end_time' => $block['end_time'],
                    'recorded_by' => $recorder->id,
                    'class_id' => $schedule->class_id,
                    'subject_id' => $schedule->subject_id,
                    'lab_booking_id' => $booking?->id,
                    'lesson_schedule_id' => $schedule->id,
                    'activity' => $activity.' — kelas '.$schedule->schoolClass?->name,
                    'participants_count' => $participants,
                    'notes' => self::SEED_MARKER.' | schedule_id='.$schedule->id.' | '.$subjectCode,
                    'incident_notes' => $incident,
                    'created_by' => $recorder->id,
                    'updated_by' => $admin->id,
                ]);

                $created++;
                $weekIndex++;
                $localWeek++;
                $cursor->addWeek();
            }
        }

        $this->command?->info(sprintf(
            'Lab journals seeded: %d entri (mengikuti %d blok jadwal, s/d %s). Refresh tab Jurnal.',
            $created,
            $blocks->count(),
            $rangeEnd->toDateString()
        ));
    }

    /**
     * @param  list<array{0:Carbon,1:Carbon}>  $ranges
     */
    private function inSkipRanges(Carbon $date, array $ranges): bool
    {
        foreach ($ranges as [$from, $to]) {
            if ($date->betweenIncluded($from, $to)) {
                return true;
            }
        }

        return false;
    }
}
