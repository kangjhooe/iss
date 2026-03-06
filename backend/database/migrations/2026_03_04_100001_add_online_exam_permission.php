<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::table('permissions')->where('key', 'online_exam')->exists()) {
            DB::table('permissions')->insert([
                'key' => 'online_exam',
                'label' => 'Ujian Online',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'online_exam')->delete();
    }
};
