<?php

namespace App\Support;

use App\Models\Student;

class StudentIdentity
{
    /**
     * Siswa aktif (termasuk kotak sampah) yang memakai NIK/NISN ini.
     * Alumni (Lulus) tidak dihitung, supaya bisa lanjut ke jenjang berikutnya.
     */
    public static function activeOccupant(string $field, string $value, ?int $exceptStudentId = null): ?Student
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $column = $field === 'nik' ? 'nik' : 'nisn';
        $query = Student::withTrashed()
            ->with('institution:id,name')
            ->where($column, $value)
            ->where('status', 'Aktif');

        if ($exceptStudentId) {
            $query->where('id', '!=', $exceptStudentId);
        }

        return $query->first();
    }

    public static function isTakenByActive(string $field, string $value, ?int $exceptStudentId = null): bool
    {
        return self::activeOccupant($field, $value, $exceptStudentId) !== null;
    }
}
