<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('correspondence', function (Blueprint $table) {
            $table->string('letter_type_code', 2)->nullable()->after('type'); // Kode jenis surat (01-16)
            $table->index('letter_type_code');
        });
        
        // Update existing null values to '16' (Surat Lainnya) if any
        DB::table('correspondence')->whereNull('letter_type_code')->update(['letter_type_code' => '16']);
        
        // Make it NOT NULL after updating existing data
        Schema::table('correspondence', function (Blueprint $table) {
            $table->string('letter_type_code', 2)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('correspondence', function (Blueprint $table) {
            $table->dropIndex(['letter_type_code']);
            $table->dropColumn('letter_type_code');
        });
    }
};
