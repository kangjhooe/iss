<?php

namespace App\Services;

use App\Models\FinanceInvoice;
use App\Models\Student;
use App\Models\StudentAttendance;
use App\Models\Violation;
use Carbon\Carbon;

class DashboardChartsService
{
    /**
     * Ringkasan chart untuk dashboard sekolah.
     *
     * @return array{
     *     students_gender: array{male:int,female:int,other:int,total:int},
     *     attendance_today: array{hadir:int,izin:int,sakit:int,alpha:int,dinas_luar:int,students_recorded:int},
     *     finance: array{billed:float,collected:float,outstanding:float},
     *     violations_by_month: list<array{month:string,label:string,count:int}>
     * }
     */
    public function build(int $institutionId): array
    {
        return [
            'students_gender' => $this->studentsGender($institutionId),
            'attendance_today' => $this->attendanceToday($institutionId),
            'finance' => $this->financeSnapshot($institutionId),
            'violations_by_month' => $this->violationsByMonth($institutionId),
        ];
    }

    /**
     * @return array{male:int,female:int,other:int,total:int}
     */
    protected function studentsGender(int $institutionId): array
    {
        $rows = Student::query()
            ->where('institution_id', $institutionId)
            ->where('status', 'Aktif')
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');

        $male = 0;
        $female = 0;
        $other = 0;
        foreach ($rows as $gender => $count) {
            $g = strtolower(trim((string) $gender));
            if (in_array($g, ['l', 'male', 'laki-laki', 'laki laki'], true)) {
                $male += (int) $count;
            } elseif (in_array($g, ['p', 'female', 'perempuan'], true)) {
                $female += (int) $count;
            } else {
                $other += (int) $count;
            }
        }

        return [
            'male' => $male,
            'female' => $female,
            'other' => $other,
            'total' => $male + $female + $other,
        ];
    }

    /**
     * Status kehadiran terakhir per siswa untuk hari ini.
     *
     * @return array{hadir:int,izin:int,sakit:int,alpha:int,dinas_luar:int,students_recorded:int}
     */
    protected function attendanceToday(int $institutionId): array
    {
        $empty = [
            'hadir' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
            'dinas_luar' => 0,
            'students_recorded' => 0,
        ];

        $today = now()->toDateString();

        $rows = StudentAttendance::query()
            ->select('student_attendances.student_id', 'student_attendances.status')
            ->join('teaching_journals', 'teaching_journals.id', '=', 'student_attendances.teaching_journal_id')
            ->where('student_attendances.institution_id', $institutionId)
            ->whereDate('teaching_journals.journal_date', $today)
            ->whereNull('student_attendances.deleted_at')
            ->orderByDesc('student_attendances.id')
            ->get();

        $latestByStudent = [];
        foreach ($rows as $row) {
            $sid = (int) $row->student_id;
            if (!isset($latestByStudent[$sid])) {
                $latestByStudent[$sid] = (string) $row->status;
            }
        }

        $counts = $empty;
        $counts['students_recorded'] = count($latestByStudent);
        foreach ($latestByStudent as $status) {
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    /**
     * @return array{billed:float,collected:float,outstanding:float}
     */
    protected function financeSnapshot(int $institutionId): array
    {
        try {
            $base = FinanceInvoice::forInstitution($institutionId)->where('status', '!=', 'cancelled');
            $billed = (float) (clone $base)->sum('amount');
            $collected = (float) (clone $base)->sum('amount_paid');

            return [
                'billed' => $billed,
                'collected' => $collected,
                'outstanding' => max(0, $billed - $collected),
            ];
        } catch (\Throwable $e) {
            return [
                'billed' => 0,
                'collected' => 0,
                'outstanding' => 0,
            ];
        }
    }

    /**
     * @return list<array{month:string,label:string,count:int}>
     */
    protected function violationsByMonth(int $institutionId): array
    {
        $start = now()->copy()->startOfMonth()->subMonths(11);
        $end = now()->copy()->endOfMonth();

        $rows = Violation::forInstitution($institutionId)
            ->where('status', '!=', Violation::STATUS_DITOLAK)
            ->whereDate('violation_date', '>=', $start->toDateString())
            ->whereDate('violation_date', '<=', $end->toDateString())
            ->get(['violation_date']);

        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $bucket = [];
        for ($i = 0; $i < 12; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $bucket[$key] = [
                'month' => $key,
                'label' => $monthNames[(int) $month->format('n') - 1] . ' ' . $month->format('Y'),
                'count' => 0,
            ];
        }

        foreach ($rows as $row) {
            $key = Carbon::parse($row->violation_date)->format('Y-m');
            if (isset($bucket[$key])) {
                $bucket[$key]['count']++;
            }
        }

        return array_values($bucket);
    }
}
