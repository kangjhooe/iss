<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_fine_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('library_loans')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->dateTime('paid_at');
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->onDelete('set null');
            $table->timestamps();

            $table->index('loan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_fine_payments');
    }
};
