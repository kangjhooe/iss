<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_item', function (Blueprint $table) {
            $table->string('funding_source', 100)->nullable()->after('supplier');
            $table->foreignId('responsible_employee_id')
                ->nullable()
                ->after('building_id')
                ->constrained('employee')
                ->nullOnDelete();
            $table->date('disposed_at')->nullable()->after('description');
            $table->text('disposal_reason')->nullable()->after('disposed_at');
            $table->string('disposal_document_number', 100)->nullable()->after('disposal_reason');

            $table->index('funding_source');
            $table->index('responsible_employee_id');
            $table->index('disposed_at');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_item', function (Blueprint $table) {
            $table->dropForeign(['responsible_employee_id']);
            $table->dropColumn([
                'funding_source',
                'responsible_employee_id',
                'disposed_at',
                'disposal_reason',
                'disposal_document_number',
            ]);
        });
    }
};
