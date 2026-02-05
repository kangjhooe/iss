<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'academic_calendar')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'academic_calendar',
                'label' => 'Kalender Akademik',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'academic_calendar')->delete();
    }
};
