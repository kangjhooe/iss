<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('program_keahlian')) {
            Schema::create('program_keahlian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
                $table->string('code', 50)->nullable();
                $table->string('name');
                $table->string('status', 20)->default('Aktif');
                $table->text('description')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['institution_id', 'code']);
                $table->index(['institution_id', 'status']);
            });
        }

        if (! Schema::hasTable('employee_program_keahlian')) {
            Schema::create('employee_program_keahlian', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
                $table->foreignId('program_keahlian_id')->constrained('program_keahlian')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['employee_id', 'program_keahlian_id'], 'emp_prog_keahlian_unique');
            });
        }

        if (Schema::hasTable('class') && ! Schema::hasColumn('class', 'program_keahlian_id')) {
            Schema::table('class', function (Blueprint $table) {
                $table->foreignId('program_keahlian_id')
                    ->nullable()
                    ->after('grade')
                    ->constrained('program_keahlian')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class') && Schema::hasColumn('class', 'program_keahlian_id')) {
            Schema::table('class', function (Blueprint $table) {
                $table->dropConstrainedForeignId('program_keahlian_id');
            });
        }

        Schema::dropIfExists('employee_program_keahlian');
        Schema::dropIfExists('program_keahlian');
    }
};
