<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->date('re_registration_deadline')->nullable()->after('close_date');
        });

        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->unsignedInteger('rank')->nullable()->after('status');
            $table->timestamp('announcement_at')->nullable()->after('rank');
            $table->date('re_registration_deadline')->nullable()->after('announcement_at');
            $table->timestamp('re_registration_confirmed_at')->nullable()->after('re_registration_deadline');
            $table->foreignId('student_id')->nullable()->after('re_registration_confirmed_at')->constrained('student')->onDelete('set null');
            $table->text('result_notes')->nullable()->after('notes');
        });

        DB::statement("ALTER TABLE ppdb_applicants MODIFY COLUMN status ENUM(
            'draft', 'submitted', 'verification', 'verified', 'rejected',
            'passed', 'reserve', 'failed', 're_registration', 'converted', 'cancelled'
        ) DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->dropColumn('re_registration_deadline');
        });

        Schema::table('ppdb_applicants', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropColumn([
                'rank', 'announcement_at', 're_registration_deadline',
                're_registration_confirmed_at', 'student_id', 'result_notes',
            ]);
        });

        DB::statement("ALTER TABLE ppdb_applicants MODIFY COLUMN status ENUM(
            'draft', 'submitted', 'verification', 'verified', 'rejected',
            'passed', 'failed', 're_registration', 'cancelled'
        ) DEFAULT 'draft'");
    }
};
