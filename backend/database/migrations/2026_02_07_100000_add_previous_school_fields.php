<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->string('previous_school_npsn', 20)->nullable()->after('previous_school');
            $table->text('previous_school_address')->nullable()->after('previous_school_npsn');
        });

        Schema::table('student', function (Blueprint $table) {
            $table->string('previous_school_npsn', 20)->nullable()->after('previous_school');
            $table->text('previous_school_address')->nullable()->after('previous_school_npsn');
        });
    }

    public function down(): void
    {
        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->dropColumn(['previous_school_npsn', 'previous_school_address']);
        });
        Schema::table('student', function (Blueprint $table) {
            $table->dropColumn(['previous_school_npsn', 'previous_school_address']);
        });
    }
};
