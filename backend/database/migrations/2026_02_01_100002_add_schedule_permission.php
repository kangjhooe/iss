<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'schedule')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'schedule',
                'label' => 'Jadwal Pelajaran',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'schedule')->delete();
    }
};
