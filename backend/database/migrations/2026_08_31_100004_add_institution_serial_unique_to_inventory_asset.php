<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = $this->indexNames('inventory_asset');

        Schema::table('inventory_asset', function (Blueprint $table) use ($indexes) {
            if (! in_array('inventory_asset_institution_serial_unique', $indexes, true)) {
                $table->unique(['institution_id', 'serial_number'], 'inventory_asset_institution_serial_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = $this->indexNames('inventory_asset');

        Schema::table('inventory_asset', function (Blueprint $table) use ($indexes) {
            if (in_array('inventory_asset_institution_serial_unique', $indexes, true)) {
                $table->dropUnique('inventory_asset_institution_serial_unique');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function indexNames(string $table): array
    {
        return collect(Schema::getIndexes($table))->pluck('name')->filter()->values()->all();
    }
};
