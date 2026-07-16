<?php

namespace App\Services;

use App\Models\EmployeeAttendance;
use App\Models\Institution;
use App\Models\LessonSchedule;
use App\Models\PiketIncident;
use App\Models\PiketLog;
use App\Models\PiketSchedule;
use App\Models\PiketSetting;
use App\Models\TeachingJournal;
use App\Models\TeacherViolation;
use App\Models\Violation;
use App\Support\InstitutionContext;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PiketService
{
    public function resolveInstitutionId($user, $requestInstitutionId = null): ?int
    {
        return InstitutionContext::resolveForUser($user, request(), $requestInstitutionId);
    }

    public function getSettings(int $institutionId): PiketSetting
    {
        return PiketSetting::forInstitution($institutionId);
    }

    public function updateSettings(int $institutionId, array $data): PiketSetting
    {
        $settings = PiketSetting::forInstitution($institutionId);
        $settings->fill($data);
        $settings->save();

        return $settings->fresh();
    }

    public function todayRoster(int $institutionId, ?string $date = null): array
    {
        $date = Carbon::parse($date ?? now()->toDateString());
        $day = $date->dayOfWeekIso;
        $settings = $this->getSettings($institutionId);

        if ($day > 5 && !$settings->include_saturday) {
            return [
                'date' => $date->toDateString(),
                'day_of_week' => $day,
                'day_name' => PiketSchedule::DAYS[$day] ?? $date->translatedFormat('l'),
                'schedules' => [],
                'logs' => [],
            ];
        }

        $schedules = PiketSchedule::with('employee:id,name,nip')
            ->forInstitution($institutionId)
            ->where('day_of_week', $day)
            ->orderBy('shift')
            ->orderBy('start_time')
            ->get();

        $logs = PiketLog::with('employee:id,name,nip')
            ->forInstitution($institutionId)
            ->whereDate('duty_date', $date->toDateString())
            ->get();

        return [
            'date' => $date->toDateString(),
            'day_of_week' => $day,
            'day_name' => PiketSchedule::DAYS[$day] ?? $date->translatedFormat('l'),
            'schedules' => $schedules,
            'logs' => $logs,
        ];
    }

    public function dashboard(int $institutionId, ?string $date = null): array
    {
        $date = Carbon::parse($date ?? now()->toDateString());
        $dateStr = $date->toDateString();
        $roster = $this->todayRoster($institutionId, $dateStr);

        $incidentCounts = PiketIncident::forInstitution($institutionId)
            ->whereDate('incident_date', $dateStr)
            ->selectRaw('incident_type, count(*) as total')
            ->groupBy('incident_type')
            ->pluck('total', 'incident_type');

        $openIncidents = PiketIncident::forInstitution($institutionId)
            ->whereDate('incident_date', $dateStr)
            ->whereIn('status', [PiketIncident::STATUS_OPEN, PiketIncident::STATUS_CONFIRMED])
            ->count();

        $pendingTeacherViolations = 0;
        if (class_exists(TeacherViolation::class)) {
            $pendingTeacherViolations = TeacherViolation::query()
                ->where('institution_id', $institutionId)
                ->where('status', 'pending')
                ->count();
        }

        $pendingStudentViolations = Violation::query()
            ->where('institution_id', $institutionId)
            ->where('status', Violation::STATUS_PENDING)
            ->whereNotNull('piket_incident_id')
            ->count();

        $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $date->copy()->endOfWeek(Carbon::FRIDAY);
        if ($this->getSettings($institutionId)->include_saturday) {
            $weekEnd = $date->copy()->endOfWeek(Carbon::SATURDAY);
        }

        $weeklyIncidents = PiketIncident::forInstitution($institutionId)
            ->whereBetween('incident_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->count();

        return [
            'date' => $dateStr,
            'roster' => $roster,
            'incident_counts' => [
                'kelas_kosong' => (int) ($incidentCounts[PiketIncident::TYPE_KELAS_KOSONG] ?? 0),
                'terlambat_guru' => (int) ($incidentCounts[PiketIncident::TYPE_TERLAMBAT_GURU] ?? 0),
                'terlambat_siswa' => (int) ($incidentCounts[PiketIncident::TYPE_TERLAMBAT_SISWA] ?? 0),
                'lainnya' => (int) ($incidentCounts[PiketIncident::TYPE_LAINNYA] ?? 0),
            ],
            'open_incidents' => $openIncidents,
            'pending_teacher_violations' => $pendingTeacherViolations,
            'pending_student_violations' => $pendingStudentViolations,
            'weekly_incidents' => $weeklyIncidents,
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
        ];
    }

    /**
     * Scan jadwal vs jurnal mengajar → kelas kosong.
     */
    public function scanEmptyClasses(int $institutionId, string $date, ?int $createdBy = null): Collection
    {
        $carbon = Carbon::parse($date);
        $day = $carbon->dayOfWeekIso;
        if ($day > 6) {
            return collect();
        }

        $institution = Institution::find($institutionId);
        $semesterId = $institution?->active_semester_id;
        if (!$semesterId) {
            return collect();
        }

        $settings = $this->getSettings($institutionId);
        $now = Carbon::now();
        $isToday = $carbon->isSameDay($now);
        $grace = (int) $settings->empty_class_grace_minutes;

        $schedules = LessonSchedule::with([
            'schoolClass:id,name,grade',
            'subject:id,name',
            'employee:id,name,nip',
        ])
            ->forInstitution($institutionId)
            ->forSemester($semesterId)
            ->where('day_of_week', $day)
            ->get();

        $journals = TeachingJournal::forInstitution($institutionId)
            ->whereDate('journal_date', $date)
            ->get(['id', 'lesson_schedule_id', 'class_id', 'employee_id', 'period']);

        $created = collect();

        foreach ($schedules as $slot) {
            if ($isToday && $slot->start_time) {
                $start = Carbon::parse($date.' '.$slot->start_time->format('H:i:s'));
                if ($now->lt($start->copy()->addMinutes($grace))) {
                    continue;
                }
            }

            $hasJournal = $journals->contains(function ($j) use ($slot) {
                if ($j->lesson_schedule_id && (int) $j->lesson_schedule_id === (int) $slot->id) {
                    return true;
                }

                return (int) $j->class_id === (int) $slot->class_id
                    && (int) $j->employee_id === (int) $slot->employee_id
                    && (int) $j->period === (int) $slot->period;
            });

            if ($hasJournal) {
                continue;
            }

            $exists = PiketIncident::forInstitution($institutionId)
                ->whereDate('incident_date', $date)
                ->where('incident_type', PiketIncident::TYPE_KELAS_KOSONG)
                ->where('lesson_schedule_id', $slot->id)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $created->push(PiketIncident::create([
                'institution_id' => $institutionId,
                'incident_date' => $date,
                'incident_type' => PiketIncident::TYPE_KELAS_KOSONG,
                'period' => $slot->period,
                'class_id' => $slot->class_id,
                'subject_id' => $slot->subject_id,
                'employee_id' => $slot->employee_id,
                'lesson_schedule_id' => $slot->id,
                'detected_at' => $now->format('H:i:s'),
                'description' => sprintf(
                    'Tidak ada jurnal mengajar untuk %s — %s jam ke-%s',
                    $slot->schoolClass?->name ?? 'Kelas',
                    $slot->subject?->name ?? 'Mapel',
                    $slot->period
                ),
                'source' => 'auto',
                'status' => PiketIncident::STATUS_OPEN,
                'created_by' => $createdBy,
            ]));
        }

        return $created;
    }

    /**
     * Scan absensi guru: check_in_time > threshold → terlambat.
     */
    public function scanTeacherLateness(int $institutionId, string $date, ?int $createdBy = null): Collection
    {
        $settings = $this->getSettings($institutionId);
        $threshold = Carbon::parse($date.' '.Carbon::parse($settings->teacher_late_threshold)->format('H:i:s'));

        $attendances = EmployeeAttendance::with('employee:id,name,nip')
            ->forInstitution($institutionId)
            ->whereDate('date', $date)
            ->where('status', EmployeeAttendance::STATUS_HADIR)
            ->whereNotNull('check_in_time')
            ->get();

        $created = collect();

        foreach ($attendances as $att) {
            $checkIn = Carbon::parse($date.' '.$att->check_in_time->format('H:i:s'));
            if ($checkIn->lte($threshold)) {
                continue;
            }

            $minutesLate = $threshold->diffInMinutes($checkIn);

            $exists = PiketIncident::forInstitution($institutionId)
                ->whereDate('incident_date', $date)
                ->where('incident_type', PiketIncident::TYPE_TERLAMBAT_GURU)
                ->where('employee_id', $att->employee_id)
                ->where('source', 'auto')
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            $created->push(PiketIncident::create([
                'institution_id' => $institutionId,
                'incident_date' => $date,
                'incident_type' => PiketIncident::TYPE_TERLAMBAT_GURU,
                'employee_id' => $att->employee_id,
                'detected_at' => $checkIn->format('H:i:s'),
                'minutes_late' => $minutesLate,
                'description' => sprintf(
                    '%s check-in %s (batas %s), terlambat %d menit',
                    $att->employee?->name ?? 'Guru',
                    $checkIn->format('H:i'),
                    $threshold->format('H:i'),
                    $minutesLate
                ),
                'source' => 'auto',
                'status' => PiketIncident::STATUS_OPEN,
                'created_by' => $createdBy,
            ]));
        }

        return $created;
    }

    public function weeklyReportData(int $institutionId, string $weekStart): array
    {
        $start = Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);
        $settings = $this->getSettings($institutionId);
        $end = $settings->include_saturday
            ? $start->copy()->endOfWeek(Carbon::SATURDAY)
            : $start->copy()->endOfWeek(Carbon::FRIDAY);

        $institution = Institution::find($institutionId);

        $logs = PiketLog::with(['employee:id,name,nip', 'incidents'])
            ->forInstitution($institutionId)
            ->whereBetween('duty_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('duty_date')
            ->get();

        $incidents = PiketIncident::with([
            'employee:id,name,nip',
            'student:id,name,nis',
            'schoolClass:id,name,grade',
            'subject:id,name',
        ])
            ->forInstitution($institutionId)
            ->whereBetween('incident_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('incident_date')
            ->orderBy('incident_type')
            ->get();

        $schedules = PiketSchedule::with('employee:id,name,nip')
            ->forInstitution($institutionId)
            ->orderBy('day_of_week')
            ->orderBy('shift')
            ->get();

        $byType = $incidents->groupBy('incident_type')->map->count();

        return [
            'institution' => $institution,
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'week_label' => $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'),
            'logs' => $logs,
            'incidents' => $incidents,
            'schedules' => $schedules,
            'summary' => [
                'total_logs' => $logs->count(),
                'total_incidents' => $incidents->count(),
                'kelas_kosong' => (int) ($byType[PiketIncident::TYPE_KELAS_KOSONG] ?? 0),
                'terlambat_guru' => (int) ($byType[PiketIncident::TYPE_TERLAMBAT_GURU] ?? 0),
                'terlambat_siswa' => (int) ($byType[PiketIncident::TYPE_TERLAMBAT_SISWA] ?? 0),
                'lainnya' => (int) ($byType[PiketIncident::TYPE_LAINNYA] ?? 0),
                'submitted_logs' => $logs->whereIn('status', [PiketLog::STATUS_SUBMITTED, PiketLog::STATUS_REVIEWED])->count(),
            ],
            'generated_at' => now()->translatedFormat('d M Y H:i'),
        ];
    }
}
