<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ppdb_channels', function (Blueprint $table) {
            if (! Schema::hasColumn('ppdb_channels', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('requirements');
            }
        });

        Schema::table('ppdb_applicant_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('ppdb_applicant_documents', 'document_key')) {
                $table->string('document_key', 64)->nullable()->after('name');
                $table->index(['ppdb_applicant_id', 'document_key'], 'ppdb_docs_applicant_key_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ppdb_applicant_documents', function (Blueprint $table) {
            if (Schema::hasColumn('ppdb_applicant_documents', 'document_key')) {
                $table->dropIndex('ppdb_docs_applicant_key_index');
                $table->dropColumn('document_key');
            }
        });

        Schema::table('ppdb_channels', function (Blueprint $table) {
            if (Schema::hasColumn('ppdb_channels', 'required_documents')) {
                $table->dropColumn('required_documents');
            }
        });
    }
};
