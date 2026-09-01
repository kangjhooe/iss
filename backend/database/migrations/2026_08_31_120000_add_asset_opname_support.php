<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_stock_opname', function (Blueprint $table) {
            $table->string('opname_type', 20)->default('stock')->after('status');
            $table->index(['institution_id', 'opname_type']);
        });

        Schema::create('inventory_asset_opname_line', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opname_id')->constrained('inventory_stock_opname')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('inventory_asset')->cascadeOnDelete();
            $table->string('book_status', 30)->nullable();
            $table->string('book_condition', 30)->nullable();
            $table->boolean('found')->nullable();
            $table->string('counted_condition', 30)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['opname_id', 'asset_id'], 'inventory_asset_opname_line_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_asset_opname_line');

        Schema::table('inventory_stock_opname', function (Blueprint $table) {
            $table->dropIndex(['institution_id', 'opname_type']);
            $table->dropColumn('opname_type');
        });
    }
};
