<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode inventaris & serial number unik per institusi, bukan global.
     */
    public function up(): void
    {
        $indexes = $this->indexNames('inventory_item');

        Schema::table('inventory_item', function (Blueprint $table) use ($indexes) {
            if (in_array('inventory_item_code_unique', $indexes, true)) {
                $table->dropUnique('inventory_item_code_unique');
            } elseif (in_array('code', $indexes, true)) {
                $table->dropUnique('code');
            }

            if (in_array('inventory_item_serial_number_unique', $indexes, true)) {
                $table->dropUnique('inventory_item_serial_number_unique');
            } elseif (in_array('serial_number', $indexes, true)) {
                $table->dropUnique('serial_number');
            }
        });

        $indexes = $this->indexNames('inventory_item');

        Schema::table('inventory_item', function (Blueprint $table) use ($indexes) {
            if (! in_array('inventory_item_institution_code_unique', $indexes, true)) {
                $table->unique(['institution_id', 'code'], 'inventory_item_institution_code_unique');
            }
        });

        // serial_number: unique per institution where not null (MySQL allows multiple NULL)
        $indexes = $this->indexNames('inventory_item');
        Schema::table('inventory_item', function (Blueprint $table) use ($indexes) {
            if (! in_array('inventory_item_institution_serial_unique', $indexes, true)) {
                $table->unique(['institution_id', 'serial_number'], 'inventory_item_institution_serial_unique');
            }
        });
    }

    public function down(): void
    {
        $indexes = $this->indexNames('inventory_item');

        Schema::table('inventory_item', function (Blueprint $table) use ($indexes) {
            if (in_array('inventory_item_institution_code_unique', $indexes, true)) {
                $table->dropUnique('inventory_item_institution_code_unique');
            }
            if (in_array('inventory_item_institution_serial_unique', $indexes, true)) {
                $table->dropUnique('inventory_item_institution_serial_unique');
            }
        });

        $indexes = $this->indexNames('inventory_item');

        Schema::table('inventory_item', function (Blueprint $table) use ($indexes) {
            if (! in_array('inventory_item_code_unique', $indexes, true)) {
                $table->unique('code');
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
