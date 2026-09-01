<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stock_opname', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->string('opname_number', 50);
            $table->date('opname_date');
            $table->foreignId('room_id')->nullable()->constrained('room')->nullOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('building')->nullOnDelete();
            $table->enum('status', ['draft', 'in_progress', 'finalized', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['institution_id', 'opname_number'], 'inventory_opname_institution_number_unique');
            $table->index(['institution_id', 'status']);
            $table->index('opname_date');
        });

        Schema::create('inventory_stock_opname_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opname_id')->constrained('inventory_stock_opname')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_item')->cascadeOnDelete();
            $table->unsignedInteger('book_quantity')->default(0);
            $table->unsignedInteger('counted_quantity')->nullable();
            $table->integer('variance')->nullable();
            $table->string('condition', 30)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('adjustment_transaction_id')->nullable()->constrained('inventory_transaction')->nullOnDelete();
            $table->timestamps();

            $table->unique(['opname_id', 'item_id'], 'inventory_opname_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_opname_line');
        Schema::dropIfExists('inventory_stock_opname');
    }
};
