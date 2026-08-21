<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            if (! Schema::hasColumn('institution', 'hidden_module_keys')) {
                $table->json('hidden_module_keys')->nullable()->after('nis_numbering');
            }
        });
    }

    public function down(): void
    {
        Schema::table('institution', function (Blueprint $table) {
            if (Schema::hasColumn('institution', 'hidden_module_keys')) {
                $table->dropColumn('hidden_module_keys');
            }
        });
    }
};
