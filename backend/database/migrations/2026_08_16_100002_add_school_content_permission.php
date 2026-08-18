<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'school_content')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'school_content',
                'label' => 'Berita & Galeri',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'school_content')->delete();
    }
};
