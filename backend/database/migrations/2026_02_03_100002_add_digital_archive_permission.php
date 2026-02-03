<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'digital_archive')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'digital_archive',
                'label' => 'Arsip Digital',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'digital_archive')->delete();
    }
};
