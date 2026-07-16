<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_loan', function (Blueprint $table) {
            $table->string('return_condition')->nullable()->after('notes');
            $table->string('return_item_status')->nullable()->after('return_condition');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_loan', function (Blueprint $table) {
            $table->dropColumn(['return_condition', 'return_item_status']);
        });
    }
};
