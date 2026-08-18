<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uks_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->string('unit', 50)->default('pcs');
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedInteger('min_stock')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['institution_id', 'is_active']);
            $table->index(['institution_id', 'name']);
        });

        Schema::create('uks_medicine_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('uks_medicine_id')->constrained('uks_medicines')->onDelete('cascade');
            $table->enum('type', ['masuk', 'keluar', 'penyesuaian'])->default('masuk');
            $table->unsignedInteger('quantity');
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->foreignId('uks_visit_id')->nullable()->constrained('uks_visits')->onDelete('set null');
            $table->foreignId('created_by')->constrained('user')->onDelete('restrict');
            $table->timestamps();

            $table->index(['institution_id', 'transaction_date']);
            $table->index(['uks_medicine_id', 'transaction_date']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uks_medicine_transactions');
        Schema::dropIfExists('uks_medicines');
    }
};
