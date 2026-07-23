<?php

use App\Support\TeacherAccess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pastikan semua akun guru (dan pegawai type Guru) punya paket mengajar:
     * correspondence, teaching_journal, grade_book, schedule.
     * Akses ini milik guru mapel, bukan hanya wali kelas.
     */
    public function up(): void
    {
        $keys = TeacherAccess::defaultPermissionKeys();
        $permissionIds = DB::table('permissions')
            ->whereIn('key', $keys)
            ->pluck('id', 'key');

        if ($permissionIds->isEmpty()) {
            return;
        }

        $userIds = DB::table('user')
            ->where('role', 'teacher')
            ->pluck('id');

        $guruEmails = DB::table('employee')
            ->where('type', 'Guru')
            ->whereNotNull('email')
            ->whereNull('deleted_at')
            ->pluck('email');

        if ($guruEmails->isNotEmpty()) {
            $fromEmployees = DB::table('user')
                ->whereIn('email', $guruEmails->all())
                ->whereIn('role', ['teacher', 'staff'])
                ->pluck('id');
            $userIds = $userIds->merge($fromEmployees)->unique()->values();
        }

        foreach ($userIds as $userId) {
            foreach ($permissionIds as $permId) {
                $exists = DB::table('user_permissions')
                    ->where('user_id', $userId)
                    ->where('permission_id', $permId)
                    ->exists();
                if (!$exists) {
                    DB::table('user_permissions')->insert([
                        'user_id' => $userId,
                        'permission_id' => $permId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Tidak mencabut: bisa menghapus akses yang memang sudah ada sebelum migrasi.
    }
};
