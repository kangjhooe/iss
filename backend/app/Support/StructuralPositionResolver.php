<?php

namespace App\Support;

use App\Models\AdditionalDuty;
use App\Models\Employee;
use App\Models\EmployeeStructuralPosition;
use App\Models\Institution;
use App\Models\Semester;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Resolve pemegang jabatan struktural / tugas tambahan pada tanggal tertentu.
 * Dipakai saat cetak dokumen agar penandatangan mengikuti tanggal dokumen, bukan tanggal cetak.
 */
class StructuralPositionResolver
{
    /**
     * @return array{role: string, name: ?string, nip: ?string, employee_id: ?int}
     */
    public static function principalAt(?Institution $institution, CarbonInterface|string|null $date = null): array
    {
        $role = Institution::principalTitleForLevel($institution?->level);

        if (!$institution) {
            return ['role' => $role, 'name' => null, 'nip' => null, 'employee_id' => null];
        }

        $holder = self::holderAt('kepala_sekolah', (int) $institution->id, $date) ?? [];

        return [
            'role' => $role,
            'name' => $holder['name'] ?? null,
            'nip' => $holder['nip'] ?? null,
            'employee_id' => $holder['employee_id'] ?? null,
        ];
    }

    /**
     * @return array{name: ?string, nip: ?string, employee_id: ?int}|null
     */
    public static function holderAt(string $positionKey, int $institutionId, CarbonInterface|string|null $date = null): ?array
    {
        $onDate = self::normalizeDate($date);

        $fromStructural = self::resolveFromStructuralPositions($positionKey, $institutionId, $onDate);
        if ($fromStructural) {
            return $fromStructural;
        }

        $fromDuty = self::resolveFromAdditionalDuties($positionKey, $institutionId, $onDate);
        if ($fromDuty) {
            return $fromDuty;
        }

        if ($onDate->isSameDay(now())) {
            $current = AdditionalDuty::resolveActiveHolder($positionKey, $institutionId);
            if ($current) {
                return self::employeePayload($current);
            }
        }

        return null;
    }

    /**
     * @return array{name: ?string, nip: ?string, employee_id: ?int}|null
     */
    protected static function resolveFromStructuralPositions(
        string $positionKey,
        int $institutionId,
        CarbonInterface $onDate
    ): ?array {
        $assignment = EmployeeStructuralPosition::query()
            ->where('institution_id', $institutionId)
            ->where('started_at', '<=', $onDate->toDateString())
            ->where(function ($q) use ($onDate) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $onDate->toDateString());
            })
            ->whereHas('position', fn ($q) => $q->where('key', $positionKey))
            ->with('employee:id,name,nip')
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->first();

        if (!$assignment?->employee) {
            return null;
        }

        return self::employeePayload($assignment->employee);
    }

    /**
     * @return array{name: ?string, nip: ?string, employee_id: ?int}|null
     */
    protected static function resolveFromAdditionalDuties(
        string $positionKey,
        int $institutionId,
        CarbonInterface $onDate
    ): ?array {
        $employee = Employee::forInstitution($institutionId)
            ->whereHas('additionalDuties', function ($query) use ($positionKey, $onDate) {
                $query->where('additional_duties.key', $positionKey)
                    ->where(function ($active) use ($onDate) {
                        $active->where(function ($started) use ($onDate) {
                            $started->whereNull('employee_additional_duties.started_at')
                                ->orWhere('employee_additional_duties.started_at', '<=', $onDate->toDateString());
                        })->where(function ($ended) use ($onDate) {
                            $ended->whereNull('employee_additional_duties.ended_at')
                                ->orWhere('employee_additional_duties.ended_at', '>=', $onDate->toDateString());
                        });
                    });
            })
            ->orderBy('name')
            ->first(['id', 'name', 'nip']);

        if (!$employee) {
            return null;
        }

        return self::employeePayload($employee);
    }

    /**
     * @return array{name: ?string, nip: ?string, employee_id: ?int}
     */
    protected static function employeePayload(Employee $employee): array
    {
        return [
            'name' => $employee->name,
            'nip' => $employee->nip,
            'employee_id' => (int) $employee->id,
        ];
    }

    protected static function normalizeDate(CarbonInterface|string|null $date): CarbonInterface
    {
        if ($date === null) {
            return now()->startOfDay();
        }

        if ($date instanceof CarbonInterface) {
            return $date->copy()->startOfDay();
        }

        return Carbon::parse($date)->startOfDay();
    }

    /**
     * Derive reference date for signatories from common report filter keys.
     */
    public static function reportAsOfDate(array $filters): CarbonInterface
    {
        if (!empty($filters['date_to'])) {
            return self::normalizeDate($filters['date_to']);
        }

        if (!empty($filters['month']) && !empty($filters['year'])) {
            return Carbon::create((int) $filters['year'], (int) $filters['month'], 1)->endOfMonth()->startOfDay();
        }

        if (!empty($filters['year'])) {
            return Carbon::create((int) $filters['year'], 12, 31)->startOfDay();
        }

        if (!empty($filters['tanggal'])) {
            return self::normalizeDate($filters['tanggal']);
        }

        return now()->startOfDay();
    }

    /**
     * Tanggal acuan penandatangan untuk laporan per semester (akhir semester).
     */
    public static function semesterAsOfDate(?Semester $semester): CarbonInterface
    {
        if ($semester?->end_date) {
            return self::normalizeDate($semester->end_date);
        }

        if ($semester?->start_date) {
            return self::normalizeDate($semester->start_date);
        }

        return now()->startOfDay();
    }

    /**
     * Tanggal acuan penandatangan untuk rekap absensi / laporan rentang tanggal.
     */
    public static function attendanceAsOfDate(array $filters, ?Semester $semester = null): CarbonInterface
    {
        if (!empty($filters['date_to'])) {
            return self::normalizeDate($filters['date_to']);
        }

        if ($semester) {
            return self::semesterAsOfDate($semester);
        }

        if (!empty($filters['date_from'])) {
            return self::normalizeDate($filters['date_from']);
        }

        return self::reportAsOfDate($filters);
    }

    /**
     * Bungkus hasil holderAt sebagai objek ringan (name/nip) untuk view.
     */
    public static function holderObject(string $positionKey, int $institutionId, CarbonInterface|string|null $date = null): ?object
    {
        $holder = self::holderAt($positionKey, $institutionId, $date);

        return $holder ? (object) $holder : null;
    }
}
