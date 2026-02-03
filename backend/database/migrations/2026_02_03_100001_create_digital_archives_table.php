<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('digital_archive_category_id')->nullable()->constrained('digital_archive_categories')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('mime_type', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->string('reference_number', 80)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['institution_id', 'document_date'], 'dig_arch_inst_date_idx');
            $table->index(['institution_id', 'digital_archive_category_id'], 'dig_arch_inst_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_archives');
    }
};
