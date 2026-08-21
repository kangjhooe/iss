<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumni_destinations', function (Blueprint $table) {
            if (! Schema::hasColumn('alumni_destinations', 'status')) {
                $table->string('status', 20)->default('approved')->after('notes');
            }
            if (! Schema::hasColumn('alumni_destinations', 'source')) {
                $table->string('source', 32)->default('manual')->after('status');
            }
            if (! Schema::hasColumn('alumni_destinations', 'related_student_id')) {
                $table->foreignId('related_student_id')->nullable()->after('source')
                    ->constrained('student')->nullOnDelete();
            }
            if (! Schema::hasColumn('alumni_destinations', 'related_institution_id')) {
                $table->foreignId('related_institution_id')->nullable()->after('related_student_id')
                    ->constrained('institution')->nullOnDelete();
            }
            if (! Schema::hasColumn('alumni_destinations', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('related_institution_id')
                    ->constrained('user')->nullOnDelete();
            }
            if (! Schema::hasColumn('alumni_destinations', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alumni_destinations', function (Blueprint $table) {
            if (Schema::hasColumn('alumni_destinations', 'reviewed_by')) {
                $table->dropConstrainedForeignId('reviewed_by');
            }
            if (Schema::hasColumn('alumni_destinations', 'related_institution_id')) {
                $table->dropConstrainedForeignId('related_institution_id');
            }
            if (Schema::hasColumn('alumni_destinations', 'related_student_id')) {
                $table->dropConstrainedForeignId('related_student_id');
            }
            foreach (['status', 'source', 'reviewed_at'] as $column) {
                if (Schema::hasColumn('alumni_destinations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
