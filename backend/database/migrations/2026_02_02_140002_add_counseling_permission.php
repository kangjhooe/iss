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
        $exists = DB::table('permissions')->where('key', 'counseling')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'counseling',
                'label' => 'Konseling',
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
        DB::table('permissions')->where('key', 'counseling')->delete();
    }
};
