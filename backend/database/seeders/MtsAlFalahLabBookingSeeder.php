<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\LabBooking;
use App\Models\Room;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Booking Lab Komputer — MTs Al-Falah Krui (variasi 6 mapel).
 *
 *   php artisan db:seed --class=MtsAlFalahLabBookingSeeder
 */
class MtsAlFalahLabBookingSeeder extends Seeder
{
    public const NPSN = '10816663';

    public const SEED_MARKER = 'SEED:LAB-JADWAL-ALFALAH';

    private const SLOT_AM = ['07:30:00', '09:30:00'];

    private const SLOT_AM2 = ['09:45:00', '11:45:00'];

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

        $teachers = [
            'rudi' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Rudi%')->first(),
            'enny' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Enny%')->first(),
            'lida' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Lida%')->first(),
            'nurpenda' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Nurpenda%')->first(),
            'sumardi' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Sumardi%')->first(),
            'novariza' => Employee::where('institution_id', $institution->id)->where('name', 'like', 'Novariza%')->first(),
        ];

        $subjects = Subject::where('institution_id', $institution->id)
            ->whereIn('code', ['COD', 'MTK', 'IPA', 'IPS', 'FQH', 'AAQ'])
            ->get()
            ->keyBy('code');

        foreach ($teachers as $key => $emp) {
            if (! $emp) {
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

        $admin = User::where('institution_id', $institution->id)->orderBy('id')->first()
            ?? User::orderBy('id')->first();
        if (! $admin) {
            $this->command?->error('User admin tidak ditemukan.');

            return;
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
            $this->command?->error('Semester Ganjil/Genap TA 2026/2027 tidak lengkap.');

            return;
        }

        LabBooking::withTrashed()
            ->where('institution_id', $institution->id)
            ->where('room_id', $room->id)
            ->where('notes', 'like', '%'.self::SEED_MARKER.'%')
            ->forceDelete();

        /**
         * Selaras dengan jadwal tetap: 6 mapel berbeda.
         *
         * @var list<array{day:int,class:string,start:string,end:string,subject:string,teacher:string}>
         */
        $weekly = [
            ['day' => Carbon::MONDAY,    'class' => 'VII-B',  'start' => self::SLOT_AM[0],  'end' => self::SLOT_AM[1],  'subject' => 'COD', 'teacher' => 'rudi'],
            ['day' => Carbon::TUESDAY,   'class' => 'VII-A',  'start' => self::SLOT_AM[0],  'end' => self::SLOT_AM[1],  'subject' => 'MTK', 'teacher' => 'nurpenda'],
            ['day' => Carbon::WEDNESDAY, 'class' => 'VIII-B', 'start' => self::SLOT_AM[0],  'end' => self::SLOT_AM[1],  'subject' => 'IPA', 'teacher' => 'lida'],
            ['day' => Carbon::THURSDAY,  'class' => 'VIII-A', 'start' => self::SLOT_AM[0],  'end' => self::SLOT_AM[1],  'subject' => 'IPS', 'teacher' => 'sumardi'],
            ['day' => Carbon::FRIDAY,    'class' => 'IX-A',   'start' => self::SLOT_AM[0],  'end' => self::SLOT_AM[1],  'subject' => 'AAQ', 'teacher' => 'enny'],
            ['day' => Carbon::FRIDAY,    'class' => 'IX-B',   'start' => self::SLOT_AM2[0], 'end' => self::SLOT_AM2[1], 'subject' => 'FQH', 'teacher' => 'novariza'],
        ];

        $created = 0;

        foreach ($semesters as $semester) {
            $start = Carbon::parse($semester->start_date)->startOfDay();
            $end = Carbon::parse($semester->end_date)->endOfDay();

            $skipRanges = [];
            if ($semester->name === 'Ganjil') {
                $skipRanges[] = [Carbon::parse('2026-12-21'), Carbon::parse('2026-12-31')];
            }
            if ($semester->name === 'Genap') {
                $skipRanges[] = [Carbon::parse('2027-01-01'), Carbon::parse('2027-01-10')];
            }

            foreach ($weekly as $slot) {
                $subject = $subjects[$slot['subject']];
                $teacher = $teachers[$slot['teacher']];
                $teacherUser = User::where('email', $teacher->email)->first() ?? $admin;

                $cursor = $start->copy();
                if ($cursor->dayOfWeek !== $slot['day']) {
                    $cursor->next($slot['day']);
                }

                while ($cursor->lte($end)) {
                    if ($this->inSkipRanges($cursor, $skipRanges)) {
                        $cursor->addWeek();
                        continue;
                    }

                    $class = $classes[$slot['class']];
                    $purpose = sprintf(
                        'Praktik %s — kelas %s (%s 2026/2027)',
                        $subject->name,
                        $class->name,
                        $semester->name
                    );

                    LabBooking::create([
                        'institution_id' => $institution->id,
                        'room_id' => $room->id,
                        'requester_employee_id' => $teacher->id,
                        'requester_name' => $teacher->name,
                        'purpose' => $purpose,
                        'date' => $cursor->toDateString(),
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'status' => 'approved',
                        'approved_by' => $admin->id,
                        'approved_at' => $cursor->copy()->subDays(3),
                        'notes' => self::SEED_MARKER.' | class_id='.$class->id.' | subject='.$subject->code.' | semester_id='.$semester->id,
                        'created_by' => $teacherUser->id,
                        'updated_by' => $admin->id,
                    ]);
                    $created++;

                    $cursor->addWeek();
                }
            }
        }

        $this->command?->info(sprintf(
            'Lab booking seeded: %d (6 mapel, room #%d).',
            $created,
            $room->id
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
