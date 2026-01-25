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
        Schema::create('inventory_loan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('inventory_item')->onDelete('cascade');
            $table->enum('borrower_type', ['Employee', 'Student', 'External'])->default('Employee');
            $table->foreignId('borrower_id')->nullable(); // ID employee atau student (polymorphic)
            $table->string('borrower_name', 255); // Untuk external atau backup
            $table->string('borrower_phone', 50)->nullable();
            $table->date('loan_date');
            $table->date('expected_return_date');
            $table->date('actual_return_date')->nullable();
            $table->integer('quantity')->default(1);
            $table->text('purpose')->nullable();
            $table->enum('status', ['Dipinjam', 'Dikembalikan', 'Terlambat', 'Hilang'])->default('Dipinjam');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('user')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('institution_id');
            $table->index('item_id');
            $table->index('borrower_type');
            $table->index('borrower_id');
            $table->index('status');
            $table->index('loan_date');
            $table->index('expected_return_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_loan');
    }
};
