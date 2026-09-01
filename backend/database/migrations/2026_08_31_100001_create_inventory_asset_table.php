<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_asset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_item')->cascadeOnDelete();
            $table->string('asset_number', 50);
            $table->string('inventory_number', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('registration_number', 100)->nullable();
            $table->string('qr_token', 512)->nullable();
            $table->enum('condition', ['Baik', 'Rusak Ringan', 'Rusak Berat', 'Habis Pakai'])->default('Baik');
            $table->enum('status', ['Tersedia', 'Dipinjam', 'Rusak', 'Hilang', 'Dijual'])->default('Tersedia');
            $table->string('disposal_status', 30)->default('active');
            $table->foreignId('room_id')->nullable()->constrained('room')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('building')->nullOnDelete();
            $table->foreignId('responsible_employee_id')->nullable()->constrained('employee')->nullOnDelete();
            $table->date('location_start_date')->nullable();
            $table->date('responsible_start_date')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->text('location_note')->nullable();
            $table->text('description')->nullable();
            $table->date('disposed_at')->nullable();
            $table->text('disposal_reason')->nullable();
            $table->string('disposal_document_number', 100)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'asset_number'], 'inventory_asset_institution_number_unique');
            $table->index('item_id');
            $table->index('qr_token');
            $table->index('status');
            $table->index('disposal_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_asset');
    }
};
