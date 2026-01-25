<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_item')->onDelete('cascade');
            $table->enum('maintenance_type', ['Perawatan', 'Perbaikan', 'Kalibrasi', 'Inspeksi'])->default('Perawatan');
            $table->date('scheduled_date');
            $table->date('completed_date')->nullable();
            $table->decimal('cost', 15, 2)->nullable();
            $table->string('vendor', 255)->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['Terjadwal', 'Dalam Proses', 'Selesai', 'Dibatalkan'])->default('Terjadwal');
            $table->string('technician_name', 255)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('user')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('institution_id');
            $table->index('item_id');
            $table->index('maintenance_type');
            $table->index('status');
            $table->index('scheduled_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_maintenance');
    }
};
