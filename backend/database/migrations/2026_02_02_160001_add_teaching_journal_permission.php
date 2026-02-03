<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'teaching_journal')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'teaching_journal',
                'label' => 'Jurnal Mengajar',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->where('key', 'teaching_journal')->delete();
    }
};
