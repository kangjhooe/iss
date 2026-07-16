<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teacher_violations')) {
            return;
        }

        Schema::table('teacher_violations', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_violations', 'piket_incident_id') && Schema::hasTable('piket_incidents')) {
                $table->foreignId('piket_incident_id')
                    ->nullable()
                    ->after('employee_id')
                    ->constrained('piket_incidents')
                    ->nullOnDelete();
                $table->unique('piket_incident_id', 'teacher_violations_piket_incident_unique');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('teacher_violations')) {
            return;
        }

        Schema::table('teacher_violations', function (Blueprint $table) {
            if (Schema::hasColumn('teacher_violations', 'piket_incident_id')) {
                $table->dropUnique('teacher_violations_piket_incident_unique');
                $table->dropConstrainedForeignId('piket_incident_id');
            }
        });
    }
};
