<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'subject_catalog_id')) {
                $table->foreignId('subject_catalog_id')
                    ->nullable()
                    ->after('institution_id')
                    ->constrained('subject_catalog')
                    ->nullOnDelete();
                $table->index(['institution_id', 'subject_catalog_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'subject_catalog_id')) {
                $table->dropConstrainedForeignId('subject_catalog_id');
            }
        });
    }
};
