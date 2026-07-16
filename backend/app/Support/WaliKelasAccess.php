<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Institution;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Akses dan scope data untuk wali kelas (homeroom).
 *
 * Status wali = ada di class.teacher_id (bukan role terpisah).
 * - Guru saja: kelas ajar via jadwal
 * - Wali (+ guru): roster & laporan BK scoped ke kelas wali
 * - Admin/TU: akses penuh lewat modul student / violation / counseling
 */
class WaliKelasAccess
{
    public static function employeeFor(User $user): ?Employee
    {
        return $user->employeeProfile()->first() ?? $user->teacherProfile()->first();
    }

    /**
     * Manajemen siswa penuh: admin, atau pemegang modul student
     * (TU / waka / grant manual — bukan paket otomatis wali).
     */
    public static function canManageStudentsFully(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        return $user->hasModuleAccess('student');
    }

    /**
     * BK penuh: admin, atau pemegang modul violation / counseling (BK, waka kesiswaan, dll).
     */
    public static function canManageBkFully(User $user): bool
    {
        if ($user->isAdminOrSuperAdmin() || $user->isInstitutionAdmin()) {
            return true;
        }

        return $user->hasModuleAccess('violation') || $user->hasModuleAccess('counseling');
    }

    /**
     * ID kelas yang diwalikan user (opsional filter tahun ajaran aktif).
     *
     * @return Collection<int, int>
     */
    public static function homeroomClassIds(User $user, ?int $academicYearId = null): Collection
    {
        $employee = self::employeeFor($user);
        if (!$employee) {
            return collect();
        }

        $query = SchoolClass::query()
            ->where('teacher_id', $employee->id);

        if ($user->institution_id) {
            $query->where('institution_id', $user->institution_id);
        }

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        } elseif ($user->institution_id) {
            $activeYearId = Institution::where('id', $user->institution_id)->value('active_academic_year_id');
            if ($activeYearId) {
                $query->where('academic_year_id', $activeYearId);
            }
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values();
    }

    public static function isHomeroomTeacher(User $user): bool
    {
        return self::homeroomClassIds($user)->isNotEmpty();
    }

    /**
     * Tanpa akses BK penuh → data laporan dibatasi ke kelas yang diwalikan (atau kosong).
     */
    public static function mustScopeBkToHomeroom(User $user): bool
    {
        return !self::canManageBkFully($user);
    }

    /**
     * Terapkan batasan class_id / class_ids pada filter laporan BK.
     * Mengembalikan null jika user scoped tapi tidak punya kelas (hasil harus kosong).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null  null = tidak boleh lihat data apa pun
     */
    public static function constrainBkFilters(User $user, array $filters): ?array
    {
        if (!self::mustScopeBkToHomeroom($user)) {
            return $filters;
        }

        $yearId = !empty($filters['academic_year_id'])
            ? (int) $filters['academic_year_id']
            : null;

        $allowedIds = self::homeroomClassIds($user, $yearId);
        if ($allowedIds->isEmpty()) {
            // Coba tanpa filter tahun (kelas wali di tahun lain)
            $allowedIds = self::homeroomClassIds($user, null);
        }

        if ($allowedIds->isEmpty()) {
            return null;
        }

        $allowed = $allowedIds->all();

        if (!empty($filters['class_id'])) {
            $requested = (int) $filters['class_id'];
            if (!in_array($requested, $allowed, true)) {
                return null;
            }
            $filters['class_ids'] = [$requested];
            unset($filters['class_id']);

            return $filters;
        }

        $filters['class_ids'] = $allowed;
        unset($filters['class_id']);

        return $filters;
    }
}
