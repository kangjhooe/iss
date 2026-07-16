<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = [
        'Aktif',
        'Cuti',
        'Pensiun',
        'Pindah',
        'Mengundurkan Diri',
        'Tidak Aktif',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('employee')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $list = "'" . implode("','", self::STATUSES) . "'";

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE employee MODIFY COLUMN status ENUM({$list}) NOT NULL DEFAULT 'Aktif'");
        } elseif (in_array($driver, ['pgsql', 'sqlite'], true)) {
            // Non-MySQL: column may already be a free-form string; no enum change needed.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('employee')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();
        $legacy = ['Aktif', 'Pensiun', 'Pindah', 'Tidak Aktif'];
        $list = "'" . implode("','", $legacy) . "'";

        if ($driver === 'mysql') {
            DB::table('employee')
                ->whereIn('status', ['Cuti', 'Mengundurkan Diri'])
                ->update(['status' => 'Tidak Aktif']);

            DB::statement("ALTER TABLE employee MODIFY COLUMN status ENUM({$list}) NOT NULL DEFAULT 'Aktif'");
        }
    }
};
