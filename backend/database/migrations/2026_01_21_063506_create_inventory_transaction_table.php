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
        Schema::create('inventory_transaction', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_item')->onDelete('cascade');
            $table->enum('transaction_type', ['Masuk', 'Keluar', 'Mutasi', 'Penyesuaian'])->default('Masuk');
            $table->date('transaction_date');
            $table->integer('quantity');
            $table->string('reference_number', 100)->nullable(); // No faktur, surat jalan, dll
            $table->foreignId('from_location_id')->nullable()->constrained('room')->onDelete('set null'); // Untuk mutasi
            $table->foreignId('to_location_id')->nullable()->constrained('room')->onDelete('set null'); // Untuk mutasi
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('user')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('institution_id');
            $table->index('item_id');
            $table->index('transaction_type');
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transaction');
    }
};
