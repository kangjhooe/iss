<?php

namespace App\Support;

/**
 * Akses modul operasional mengajar untuk semua guru mapel
 * (bukan hanya wali kelas).
 *
 * Persuratan (correspondence) sengaja tidak termasuk — hanya admin /
 * tugas tambahan administratif (TU, Humas, Kepala Sekolah, Hubin, dll.).
 * Guru penerima disposisi memakai API inbox disposisi terpisah.
 */
class TeacherAccess
{
    /**
     * Permission default agar guru bisa jurnal, nilai, absensi (via jurnal), dan lihat jadwal.
     *
     * @return list<string>
     */
    public static function defaultPermissionKeys(): array
    {
        return [
            'teaching_journal',
            'grade_book',
            'schedule',
        ];
    }

    /**
     * Merge keys manual dengan paket mengajar (tidak mengurangi yang sudah ada).
     *
     * @param  list<string>|null  $keys
     * @return list<string>
     */
    public static function mergeTeachingDefaults(?array $keys): array
    {
        return array_values(array_unique(array_merge(
            self::defaultPermissionKeys(),
            $keys ?? []
        )));
    }
}
