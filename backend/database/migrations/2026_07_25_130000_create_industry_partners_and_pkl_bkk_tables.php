<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industry_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('business_field', 255)->nullable();
            $table->string('address', 500)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('pic_name', 150)->nullable();
            $table->string('pic_phone', 50)->nullable();
            $table->string('status', 20)->default('Aktif');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'status']);
            $table->index(['institution_id', 'name']);
        });

        Schema::create('pkl_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('name', 255);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'status']);
        });

        Schema::create('pkl_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('pkl_period_id')->constrained('pkl_periods')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->foreignId('industry_partner_id')->constrained('industry_partners')->onDelete('restrict');
            $table->foreignId('supervisor_employee_id')->nullable()->constrained('employee')->nullOnDelete();
            $table->string('industry_supervisor_name', 150)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['pkl_period_id', 'student_id']);
            $table->index(['institution_id', 'status']);
            $table->index('industry_partner_id');
        });

        Schema::create('pkl_monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('pkl_placement_id')->constrained('pkl_placements')->onDelete('cascade');
            $table->foreignId('logged_by_employee_id')->nullable()->constrained('employee')->nullOnDelete();
            $table->date('visit_date');
            $table->string('method', 30)->default('kunjungan');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['pkl_placement_id', 'visit_date']);
        });

        Schema::create('bkk_vacancies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('industry_partner_id')->nullable()->constrained('industry_partners')->nullOnDelete();
            $table->string('title', 255);
            $table->string('company_name', 255)->nullable();
            $table->string('position', 255)->nullable();
            $table->unsignedInteger('quota')->nullable();
            $table->date('deadline')->nullable();
            $table->string('status', 20)->default('buka');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'status']);
        });

        Schema::create('bkk_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('bkk_vacancy_id')->constrained('bkk_vacancies')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade');
            $table->string('status', 20)->default('diajukan');
            $table->date('applied_at')->nullable();
            $table->foreignId('alumni_destination_id')->nullable()->constrained('alumni_destinations')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['bkk_vacancy_id', 'student_id']);
            $table->index(['institution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkk_applications');
        Schema::dropIfExists('bkk_vacancies');
        Schema::dropIfExists('pkl_monitoring_logs');
        Schema::dropIfExists('pkl_placements');
        Schema::dropIfExists('pkl_periods');
        Schema::dropIfExists('industry_partners');
    }
};
