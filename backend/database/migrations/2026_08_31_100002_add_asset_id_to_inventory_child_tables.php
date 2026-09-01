<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_loan', function (Blueprint $table) {
            $table->foreignId('asset_id')
                ->nullable()
                ->after('item_id')
                ->constrained('inventory_asset')
                ->nullOnDelete();
            $table->index('asset_id');
        });

        Schema::table('inventory_maintenance', function (Blueprint $table) {
            $table->foreignId('asset_id')
                ->nullable()
                ->after('item_id')
                ->constrained('inventory_asset')
                ->nullOnDelete();
            $table->index('asset_id');
        });

        Schema::table('inventory_disposal', function (Blueprint $table) {
            $table->foreignId('asset_id')
                ->nullable()
                ->after('item_id')
                ->constrained('inventory_asset')
                ->nullOnDelete();
            $table->index('asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_disposal', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
        });

        Schema::table('inventory_maintenance', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
        });

        Schema::table('inventory_loan', function (Blueprint $table) {
            $table->dropForeign(['asset_id']);
            $table->dropColumn('asset_id');
        });
    }
};
