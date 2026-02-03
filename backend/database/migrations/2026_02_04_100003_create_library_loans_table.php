<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->onDelete('cascade');
            $table->foreignId('copy_id')->constrained('library_book_copies')->onDelete('restrict');
            $table->enum('borrower_type', ['Student', 'Employee', 'External']);
            $table->unsignedBigInteger('borrower_id')->nullable();
            $table->string('borrower_name', 255);
            $table->string('borrower_identifier', 100)->nullable();
            $table->date('loan_date');
            $table->date('due_date');
            $table->dateTime('returned_at')->nullable();
            $table->enum('status', ['Dipinjam', 'Dikembalikan', 'Terlambat', 'Hilang', 'Rusak'])->default('Dipinjam');
            $table->decimal('fine_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamps();

            $table->index('institution_id');
            $table->index('copy_id');
            $table->index('borrower_type');
            $table->index('borrower_id');
            $table->index('status');
            $table->index('loan_date');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_loans');
    }
};
