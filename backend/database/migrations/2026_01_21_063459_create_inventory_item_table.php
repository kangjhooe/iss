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
        Schema::create('inventory_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('inventory_category')->onDelete('restrict');
            $table->string('code', 100)->unique(); // Format: MEU/001BKBA/10816663/2026
            $table->string('name', 255);
            $table->string('brand', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->string('supplier', 255)->nullable();
            $table->enum('condition', ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Habis Pakai'])->default('Baik');
            $table->enum('status', ['Tersedia', 'Dipinjam', 'Rusak', 'Hilang', 'Dijual'])->default('Tersedia');
            $table->integer('quantity')->default(1);
            $table->string('unit', 50)->default('Unit');
            $table->foreignId('room_id')->nullable()->constrained('room')->onDelete('set null');
            $table->foreignId('building_id')->nullable()->constrained('building')->onDelete('set null');
            $table->text('location_note')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('created_by')->constrained('user')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('institution_id');
            $table->index('category_id');
            $table->index('code');
            $table->index(['room_id', 'building_id']);
            $table->index('status');
            $table->index('condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item');
    }
};
