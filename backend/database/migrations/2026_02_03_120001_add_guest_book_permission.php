<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('permissions')->where('key', 'guest_book')->exists();
        if (!$exists) {
            DB::table('permissions')->insert([
                'key' => 'guest_book',
                'label' => 'Buku Tamu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('key', 'guest_book')->delete();
    }
};
