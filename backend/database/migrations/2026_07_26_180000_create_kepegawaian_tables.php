<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('structural_positions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->string('leave_type', 40);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'status'], 'emp_leave_inst_status_idx');
            $table->index(['employee_id', 'start_date', 'end_date'], 'emp_leave_emp_dates_idx');
        });

        Schema::create('employee_decrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->string('decree_type', 40);
            $table->string('number');
            $table->string('title');
            $table->date('decree_date');
            $table->date('effective_date')->nullable();
            $table->date('end_date')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'decree_type'], 'emp_decree_inst_type_idx');
            $table->index(['employee_id', 'decree_date'], 'emp_decree_emp_date_idx');
        });

        Schema::create('employee_structural_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employee')->cascadeOnDelete();
            $table->foreignId('structural_position_id')->constrained('structural_positions')->cascadeOnDelete();
            $table->foreignId('employee_decree_id')->nullable()->constrained('employee_decrees')->nullOnDelete();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->string('decree_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'structural_position_id', 'ended_at'], 'emp_struct_inst_pos_end_idx');
            $table->index(['employee_id', 'ended_at'], 'emp_struct_emp_end_idx');
        });

        $now = now();
        $positions = [
            ['key' => 'kepala_sekolah', 'label' => 'Kepala Sekolah', 'sort_order' => 10],
            ['key' => 'waka_kurikulum', 'label' => 'Wakil Kepala Sekolah Kurikulum', 'sort_order' => 20],
            ['key' => 'waka_kesiswaan', 'label' => 'Wakil Kepala Sekolah Kesiswaan', 'sort_order' => 30],
            ['key' => 'waka_sarpras', 'label' => 'Wakil Kepala Sekolah Sarana Prasarana', 'sort_order' => 40],
            ['key' => 'waka_humas', 'label' => 'Wakil Kepala Sekolah Humas', 'sort_order' => 50],
            ['key' => 'kepala_tata_usaha', 'label' => 'Kepala Tata Usaha', 'sort_order' => 60],
            ['key' => 'bendahara', 'label' => 'Bendahara', 'sort_order' => 70],
            ['key' => 'ketua_perpus', 'label' => 'Kepala Perpustakaan', 'sort_order' => 80],
            ['key' => 'kepala_lab', 'label' => 'Kepala Lab', 'sort_order' => 90],
            ['key' => 'kepala_program_keahlian', 'label' => 'Kepala Program Keahlian', 'sort_order' => 100],
            ['key' => 'kepala_bengkel', 'label' => 'Kepala Bengkel', 'sort_order' => 110],
        ];

        foreach ($positions as $position) {
            DB::table('structural_positions')->insert([
                'key' => $position['key'],
                'label' => $position['label'],
                'description' => 'Jabatan struktural resmi sekolah',
                'sort_order' => $position['sort_order'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_structural_positions');
        Schema::dropIfExists('employee_decrees');
        Schema::dropIfExists('employee_leave_requests');
        Schema::dropIfExists('structural_positions');
    }
};
