<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_need_analysis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->nullOnDelete();
            $table->string('academic_year_label', 100)->nullable();
            $table->string('title', 200);
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('rombel_count')->default(0);
            $table->unsignedInteger('shortage_lines')->default(0);
            $table->unsignedInteger('high_priority_lines')->default(0);
            $table->decimal('estimated_budget', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('counts_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'academic_year_id']);
            $table->index(['institution_id', 'status']);
        });

        Schema::create('inventory_need_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained('inventory_need_analysis')->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->string('section', 20);
            $table->string('name', 150);
            $table->string('unit', 30)->default('Unit');
            $table->string('basis_type', 20)->default('manual');
            $table->string('basis_room_type', 50)->nullable();
            $table->string('room_type', 50)->nullable();
            $table->unsignedInteger('basis_value')->default(0);
            $table->unsignedInteger('ratio_numerator')->default(1);
            $table->unsignedInteger('ratio_denominator')->default(1);
            $table->string('usable_policy', 30)->default('baik_dan_ringan');
            $table->foreignId('category_id')->nullable()->constrained('inventory_category')->nullOnDelete();
            $table->json('source_item_ids')->nullable();
            $table->string('priority', 20)->default('sedang');
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('existing_baik')->default(0);
            $table->unsignedInteger('existing_rusak_ringan')->default(0);
            $table->unsignedInteger('existing_rusak_sedang')->default(0);
            $table->unsignedInteger('existing_rusak_berat')->default(0);
            $table->unsignedInteger('existing_habis')->default(0);
            $table->unsignedInteger('required_qty')->default(0);
            $table->unsignedInteger('usable_qty')->default(0);
            $table->unsignedInteger('shortage_qty')->default(0);
            $table->unsignedInteger('surplus_qty')->default(0);
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->timestamps();

            $table->index(['analysis_id', 'section', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_need_line');
        Schema::dropIfExists('inventory_need_analysis');
    }
};
