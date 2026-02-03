<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'document_pickup')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'document_pickup',
                'label' => 'Pengambilan Ijazah',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'document_pickup')->delete();
    }
};
