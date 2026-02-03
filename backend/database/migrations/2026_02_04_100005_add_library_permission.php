<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'library')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'library',
                'label' => 'Perpustakaan',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'library')->delete();
    }
};
