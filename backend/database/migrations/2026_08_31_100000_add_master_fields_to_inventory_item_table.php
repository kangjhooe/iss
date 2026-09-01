<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_item', function (Blueprint $table) {
            $table->string('tracking_type', 20)->default('stock')->after('category_id');
            $table->string('master_code', 100)->nullable()->after('code');
            $table->string('legacy_code', 100)->nullable()->after('master_code');
            $table->string('identity_status', 30)->default('complete')->after('tracking_type');
            $table->string('ownership_type', 50)->nullable()->after('funding_source');
            $table->string('owner_name', 255)->nullable()->after('ownership_type');
            $table->string('ownership_document_number', 100)->nullable()->after('owner_name');
            $table->date('ownership_date')->nullable()->after('ownership_document_number');
            $table->text('ownership_notes')->nullable()->after('ownership_date');
            $table->string('acquisition_method', 50)->nullable()->after('supplier');
            $table->decimal('additional_cost', 15, 2)->nullable()->after('purchase_price');
            $table->decimal('book_value', 15, 2)->nullable()->after('additional_cost');
            $table->text('valuation_notes')->nullable()->after('book_value');

            $table->index('tracking_type');
            $table->index('identity_status');
            $table->index('ownership_type');
        });

        // Backfill master/legacy code dari kode existing
        if (Schema::hasColumn('inventory_item', 'code')) {
            DB::table('inventory_item')
                ->whereNull('master_code')
                ->update([
                    'master_code' => DB::raw('code'),
                    'legacy_code' => DB::raw('code'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('inventory_item', function (Blueprint $table) {
            $table->dropColumn([
                'tracking_type',
                'master_code',
                'legacy_code',
                'identity_status',
                'ownership_type',
                'owner_name',
                'ownership_document_number',
                'ownership_date',
                'ownership_notes',
                'acquisition_method',
                'additional_cost',
                'book_value',
                'valuation_notes',
            ]);
        });
    }
};
