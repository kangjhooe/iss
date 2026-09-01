<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_asset_movement', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained('institution')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('inventory_asset')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('inventory_item')->cascadeOnDelete();
            $table->date('movement_date');
            $table->foreignId('from_room_id')->nullable()->constrained('room')->nullOnDelete();
            $table->foreignId('to_room_id')->nullable()->constrained('room')->nullOnDelete();
            $table->foreignId('from_building_id')->nullable()->constrained('building')->nullOnDelete();
            $table->foreignId('to_building_id')->nullable()->constrained('building')->nullOnDelete();
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();

            $table->index(['institution_id', 'movement_date']);
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_asset_movement');
    }
};
